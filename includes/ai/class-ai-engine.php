<?php
namespace WPTravelMachine\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

class AIEngine {
    public function __construct() {
        add_action( 'wp_ajax_wptm_ai_recommend', array( $this, 'recommend' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_recommend', array( $this, 'recommend' ) );
        add_action( 'wp_ajax_wptm_ai_search', array( $this, 'smart_search' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_search', array( $this, 'smart_search' ) );
        add_action( 'wp_ajax_wptm_ai_itinerary', array( $this, 'generate_itinerary' ) );
        add_action( 'wp_ajax_wptm_ai_chat', array( $this, 'chat' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_chat', array( $this, 'chat' ) );
    }

    private function is_enabled() {
        return (bool) get_option( 'wptm_enable_ai', false ) && ! empty( get_option( 'wptm_ai_api_key', '' ) );
    }

    /**
     * Per-visitor throttle for the public AI endpoints.
     *
     * These endpoints proxy a paid LLM API and are reachable by logged-out
     * visitors (nopriv), so without a limit a bot could rack up API costs.
     *
     * @return bool True when the request is allowed; false when rate-limited.
     */
    private function rate_limit_ok() {
        $id      = get_current_user_id();
        $bucket  = $id ? 'u' . $id : 'ip' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
        $key     = 'wptm_ai_rl_' . $bucket;
        $hits    = (int) get_transient( $key );

        /**
         * Filter the max number of AI requests allowed per visitor per minute.
         *
         * @param int $max Maximum requests per minute.
         */
        $max = (int) apply_filters( 'wptm_ai_rate_limit', 10 );
        if ( $hits >= $max ) {
            return false;
        }
        set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );
        return true;
    }

    /**
     * Call the configured AI provider.
     *
     * @return string|\WP_Error The reply text, or a WP_Error describing the failure.
     */
    private function call_api( $prompt, $max_tokens = 1000 ) {
        $provider = get_option( 'wptm_ai_provider', 'openai' );
        $key      = get_option( 'wptm_ai_api_key', '' );
        $model    = trim( (string) get_option( 'wptm_ai_model', '' ) );

        if ( empty( $key ) ) {
            return new \WP_Error( 'wptm_ai_no_key', __( 'AI API key is not configured.', 'wp-travel-machine' ) );
        }

        // Anthropic uses its own request shape; everything else (OpenAI, Groq,
        // Gemini, OpenRouter, Ollama, …) speaks the OpenAI chat-completions format.
        $is_anthropic = ( 'anthropic' === $provider );

        if ( $is_anthropic ) {
            $url  = 'https://api.anthropic.com/v1/messages';
            $body = array(
                'model'      => $model ?: 'claude-opus-4-8',
                'max_tokens' => $max_tokens,
                'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
            );
            $headers = array(
                'x-api-key'         => $key,
                'Content-Type'      => 'application/json',
                'anthropic-version' => '2023-06-01',
            );
        } else {
            // Resolve the chat-completions endpoint.
            if ( 'custom' === $provider ) {
                $base = untrailingslashit( trim( (string) get_option( 'wptm_ai_base_url', '' ) ) );
                if ( empty( $base ) ) {
                    return new \WP_Error( 'wptm_ai_no_base_url', __( 'A Base URL is required for the custom AI provider.', 'wp-travel-machine' ) );
                }
                if ( empty( $model ) ) {
                    return new \WP_Error( 'wptm_ai_no_model', __( 'A model name is required for the custom AI provider.', 'wp-travel-machine' ) );
                }
                // Accept a base ending in /v1 or the full /chat/completions path.
                $url = ( false !== strpos( $base, '/chat/completions' ) ) ? $base : $base . '/chat/completions';
            } else {
                $url   = 'https://api.openai.com/v1/chat/completions';
                $model = $model ?: 'gpt-4o-mini';
            }

            $body = array(
                'model'      => $model,
                'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
                'max_tokens' => $max_tokens,
            );
            $headers = array(
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            );
        }

        $resp = wp_remote_post( $url, array(
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $resp ) ) {
            return $resp;
        }

        $code = (int) wp_remote_retrieve_response_code( $resp );
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( $code < 200 || $code >= 300 ) {
            // OpenAI-style: error.message; Anthropic-style: error.message too.
            $msg = $body['error']['message'] ?? ( is_string( $body['error'] ?? null ) ? $body['error'] : '' );
            return new \WP_Error( 'wptm_ai_http_error', $msg ?: sprintf( __( 'AI request failed (HTTP %d).', 'wp-travel-machine' ), $code ) );
        }

        $text = $is_anthropic
            ? ( $body['content'][0]['text'] ?? '' )
            : ( $body['choices'][0]['message']['content'] ?? '' );

        if ( '' === trim( (string) $text ) ) {
            return new \WP_Error( 'wptm_ai_empty', __( 'The AI returned an empty response.', 'wp-travel-machine' ) );
        }

        return $text;
    }

    public function recommend() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() ) wp_send_json_error( array( 'message' => 'AI not enabled.' ) );
        if ( ! $this->rate_limit_ok() ) wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'wp-travel-machine' ) ), 429 );

        $prefs = sanitize_text_field( wp_unslash( $_POST['preferences'] ?? '' ) );
        $budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) );

        // Get available trips for context.
        $trips = get_posts( array( 'post_type' => 'wptm_trip', 'posts_per_page' => 20, 'post_status' => 'publish' ) );
        $trip_list = '';
        foreach ( $trips as $t ) {
            $p = get_post_meta( $t->ID, '_wptm_pricing', true );
            $price = is_array( $p ) && ! empty( $p ) ? $p[0]['price'] : 0;
            $trip_list .= "- {$t->post_title} (\${$price}, " . get_post_meta( $t->ID, '_wptm_duration', true ) . " days)\n";
        }

        $prompt = "You are a travel advisor. Based on these preferences: '{$prefs}', budget: '{$budget}', recommend trips from this list:\n{$trip_list}\nProvide top 3 recommendations with reasons. Format as JSON array with keys: title, reason, match_score (1-100).";

        $result = $this->call_api( $prompt );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        wp_send_json_success( array( 'recommendations' => $result ) );
    }

    public function smart_search() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() ) {
            // Fallback to regular search.
            wp_send_json_success( array( 'mode' => 'standard' ) );
            return;
        }
        if ( ! $this->rate_limit_ok() ) {
            wp_send_json_success( array( 'mode' => 'standard' ) );
            return;
        }

        $query = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
        $prompt = "Parse this travel search query into structured filters. Query: '{$query}'. Return JSON with keys: destination, duration_days, max_budget, activity_type, difficulty, guests. Only include keys you can extract.";

        $result = $this->call_api( $prompt, 200 );
        if ( is_wp_error( $result ) ) {
            // Don't break search if the AI is misconfigured — fall back to standard.
            wp_send_json_success( array( 'mode' => 'standard', 'query' => $query ) );
            return;
        }
        $filters = json_decode( $result, true );

        if ( ! is_array( $filters ) ) {
            wp_send_json_success( array( 'mode' => 'standard', 'query' => $query ) );
            return;
        }

        wp_send_json_success( array( 'mode' => 'ai', 'filters' => $filters, 'original_query' => $query ) );
    }

    public function generate_itinerary() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() || ! current_user_can( 'edit_posts' ) ) wp_send_json_error();

        $dest = sanitize_text_field( wp_unslash( $_POST['destination'] ?? '' ) );
        $days = absint( $_POST['days'] ?? 3 );

        $prompt = "Create a {$days}-day travel itinerary for {$dest}. For each day provide: title, description, meals, accommodation. Format as JSON array.";
        $result = $this->call_api( $prompt, 1500 );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'itinerary' => $result ) );
    }

    public function chat() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() ) wp_send_json_error( array( 'message' => 'AI chat not available.' ) );
        if ( ! $this->rate_limit_ok() ) wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'wp-travel-machine' ) ), 429 );

        $message = sanitize_text_field( wp_unslash( $_POST['message'] ?? '' ) );
        $trips = get_posts( array( 'post_type' => 'wptm_trip', 'posts_per_page' => 10, 'post_status' => 'publish' ) );
        $context = '';
        foreach ( $trips as $t ) $context .= "- {$t->post_title}: " . wp_trim_words( $t->post_content, 15 ) . "\n";

        $prompt = "You are a friendly travel assistant for a booking website. Available trips:\n{$context}\nUser asks: {$message}\nProvide helpful, concise travel advice.";
        $reply = $this->call_api( $prompt, 500 );
        if ( is_wp_error( $reply ) ) {
            wp_send_json_error( array( 'message' => $reply->get_error_message() ) );
        }

        wp_send_json_success( array( 'reply' => $reply ) );
    }
}
