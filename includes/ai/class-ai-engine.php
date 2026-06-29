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
        add_action( 'wp_ajax_wptm_ai_generate_trip', array( $this, 'generate_trip' ) );
        add_action( 'wp_ajax_wptm_ai_draft_reply', array( $this, 'draft_reply' ) );
        add_action( 'wp_ajax_wptm_ai_chat', array( $this, 'chat' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_chat', array( $this, 'chat' ) );
    }

    private function is_enabled() {
        // AI is a Pro feature.
        return wptm_is_pro() && (bool) get_option( 'wptm_enable_ai', false ) && ! empty( get_option( 'wptm_ai_api_key', '' ) );
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

    /**
     * Generate a complete trip — description, highlights, itinerary, inclusions,
     * FAQ and suggested facts — from a few inputs, as a single structured object.
     */
    public function generate_trip() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() || ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'AI is not available.', 'wp-travel-machine' ) ) );
        }
        if ( ! $this->rate_limit_ok() ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'wp-travel-machine' ) ), 429 );
        }

        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $dest  = sanitize_text_field( wp_unslash( $_POST['destination'] ?? '' ) );
        $days  = max( 1, min( 30, absint( $_POST['days'] ?? 5 ) ) );
        $style = sanitize_text_field( wp_unslash( $_POST['style'] ?? 'adventure' ) );
        $budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? 'mid-range' ) );
        $audience = sanitize_text_field( wp_unslash( $_POST['audience'] ?? '' ) );

        $subject = $dest ?: $title;
        if ( '' === trim( $subject ) ) {
            wp_send_json_error( array( 'message' => __( 'Add a trip title or destination first.', 'wp-travel-machine' ) ) );
        }

        $currency = get_option( 'wptm_currency_symbol', '$' );

        $prompt =
            "You are an expert travel product copywriter for a tour operator. Create a complete, ready-to-publish trip package.\n\n" .
            "TRIP: " . ( $title ?: $subject ) . "\n" .
            "DESTINATION: {$subject}\n" .
            "DURATION: {$days} days\n" .
            "STYLE: {$style}\n" .
            "BUDGET LEVEL: {$budget}\n" .
            ( $audience ? "TARGET TRAVELLERS: {$audience}\n" : '' ) .
            "\nRespond with ONLY a single valid JSON object (no markdown, no code fences, no commentary) using EXACTLY these keys:\n" .
            "{\n" .
            '  "excerpt": "1-2 sentence hook (max 240 chars)",' . "\n" .
            '  "description": "3-4 vivid paragraphs of marketing prose. Separate paragraphs with \\n\\n. No headings.",' . "\n" .
            '  "highlights": ["6-8 short punchy highlights"],' . "\n" .
            '  "includes": ["6-10 specific included items"],' . "\n" .
            '  "excludes": ["4-6 specific excluded items"],' . "\n" .
            '  "itinerary": [{"title":"Day 1: ...","description":"2-3 sentences","meals":"Breakfast, Dinner","accommodation":"Hotel/lodge name or type"}],' . "\n" .
            '  "faq": [{"question":"...","answer":"..."}],' . "\n" .
            '  "suggested": {"duration":' . $days . ',"difficulty":"easy|moderate|challenging|difficult|extreme","group_min":2,"group_max":12,"min_age":0,"price":0}' . "\n" .
            "}\n\n" .
            "Rules: itinerary MUST have exactly {$days} day objects. Prices are a realistic per-person amount in {$currency} as a plain number. Keep it specific to {$subject}, not generic.";

        $result = $this->call_api( $prompt, 3000 );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $data = $this->extract_json( $result );
        if ( ! is_array( $data ) ) {
            wp_send_json_error( array( 'message' => __( 'The AI response could not be read. Please try again.', 'wp-travel-machine' ) ) );
        }

        wp_send_json_success( array( 'trip' => $this->normalize_trip( $data ) ) );
    }

    /**
     * Extract the first JSON object/array from a model reply, tolerating
     * ``` fences and surrounding prose.
     *
     * @param string $text Raw model output.
     * @return array|null
     */
    private function extract_json( $text ) {
        $text = (string) $text;

        // Strip ``` / ```json fences if present.
        if ( false !== strpos( $text, '```' ) ) {
            $text = preg_replace( '/```(?:json)?/i', '', $text );
        }

        // Prefer an object; fall back to an array.
        foreach ( array( array( '{', '}' ), array( '[', ']' ) ) as $pair ) {
            $start = strpos( $text, $pair[0] );
            $end   = strrpos( $text, $pair[1] );
            if ( false !== $start && false !== $end && $end > $start ) {
                $decoded = json_decode( substr( $text, $start, $end - $start + 1 ), true );
                if ( is_array( $decoded ) ) {
                    return $decoded;
                }
            }
        }
        return null;
    }

    /**
     * Normalize/whitelist the AI trip payload into the exact shape the editor
     * expects, so the front-end never has to guess at field names.
     *
     * @param array $d Raw decoded AI data.
     * @return array
     */
    private function normalize_trip( $d ) {
        $list = function ( $v ) {
            $out = array();
            foreach ( (array) ( $v ?? array() ) as $item ) {
                if ( is_array( $item ) ) {
                    $item = $item['text'] ?? $item['title'] ?? $item['name'] ?? reset( $item );
                }
                $item = trim( (string) $item );
                if ( '' !== $item ) {
                    $out[] = $item;
                }
            }
            return $out;
        };

        $itinerary = array();
        foreach ( (array) ( $d['itinerary'] ?? array() ) as $day ) {
            if ( ! is_array( $day ) ) {
                continue;
            }
            $flat = function ( $v ) {
                if ( is_array( $v ) ) {
                    return implode( ', ', array_filter( array_map( 'strval', $v ) ) );
                }
                return (string) $v;
            };
            $itinerary[] = array(
                'title'         => (string) ( $day['title'] ?? $day['name'] ?? '' ),
                'description'   => (string) ( $day['description'] ?? $day['desc'] ?? '' ),
                'meals'         => $flat( $day['meals'] ?? '' ),
                'accommodation' => $flat( $day['accommodation'] ?? $day['hotel'] ?? '' ),
            );
        }

        $faq = array();
        foreach ( (array) ( $d['faq'] ?? array() ) as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $q = trim( (string) ( $row['question'] ?? $row['q'] ?? '' ) );
            $a = trim( (string) ( $row['answer'] ?? $row['a'] ?? '' ) );
            if ( '' !== $q || '' !== $a ) {
                $faq[] = array( 'question' => $q, 'answer' => $a );
            }
        }

        $s = is_array( $d['suggested'] ?? null ) ? $d['suggested'] : array();
        $allowed_diff = array( 'easy', 'moderate', 'challenging', 'difficult', 'extreme' );
        $difficulty   = strtolower( (string) ( $s['difficulty'] ?? 'moderate' ) );

        return array(
            'excerpt'     => trim( (string) ( $d['excerpt'] ?? '' ) ),
            'description' => trim( (string) ( $d['description'] ?? '' ) ),
            'highlights'  => $list( $d['highlights'] ?? array() ),
            'includes'    => $list( $d['includes'] ?? array() ),
            'excludes'    => $list( $d['excludes'] ?? array() ),
            'itinerary'   => $itinerary,
            'faq'         => $faq,
            'suggested'   => array(
                'duration'   => absint( $s['duration'] ?? 0 ),
                'difficulty' => in_array( $difficulty, $allowed_diff, true ) ? $difficulty : 'moderate',
                'group_min'  => absint( $s['group_min'] ?? 0 ),
                'group_max'  => absint( $s['group_max'] ?? 0 ),
                'min_age'    => absint( $s['min_age'] ?? 0 ),
                'price'      => round( (float) ( $s['price'] ?? 0 ), 2 ),
            ),
        );
    }

    /**
     * Draft a personalized customer email reply for a booking, using the
     * booking context. Returns the body text and a suggested subject.
     */
    public function draft_reply() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'AI is not available.', 'wp-travel-machine' ) ) );
        }
        if ( ! $this->rate_limit_ok() ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'wp-travel-machine' ) ), 429 );
        }

        $id      = absint( $_POST['booking_id'] ?? 0 );
        $booking = \WPTravelMachine\Booking\BookingEngine::get_booking( $id );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'wp-travel-machine' ) ) );
        }

        $intent = sanitize_textarea_field( wp_unslash( $_POST['intent'] ?? '' ) );
        $tone   = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'friendly' ) );
        $allowed_tones = array( 'friendly', 'professional', 'apologetic', 'enthusiastic' );
        if ( ! in_array( $tone, $allowed_tones, true ) ) {
            $tone = 'friendly';
        }

        $sym     = get_option( 'wptm_currency_symbol', '$' );
        $company = \WPTravelMachine\Booking\Invoice::business();
        $item    = get_the_title( $booking->item_id ) ?: __( 'their booking', 'wp-travel-machine' );

        $ctx  = "Customer name: {$booking->customer_name}\n";
        $ctx .= "Booking reference: {$booking->booking_number}\n";
        $ctx .= "Item: {$item}\n";
        $ctx .= "Travelers: " . (int) $booking->travelers_count . "\n";
        if ( $booking->check_in && '0000-00-00' !== substr( (string) $booking->check_in, 0, 10 ) ) {
            $ctx .= "Check-in: {$booking->check_in}\n";
        }
        $ctx .= "Booking status: {$booking->status}\n";
        $ctx .= "Payment status: {$booking->payment_status}\n";
        $ctx .= "Total: {$sym}" . number_format( (float) $booking->total_price, 2 ) . "\n";
        if ( ! empty( $booking->notes ) ) {
            $ctx .= "Customer's special requests / message: {$booking->notes}\n";
        }

        $prompt =
            "You are a customer-support agent for \"{$company['name']}\", a travel booking company. " .
            "Write the BODY of a warm, {$tone}, well-formatted email reply to this customer about their booking.\n\n" .
            "BOOKING CONTEXT:\n{$ctx}\n" .
            ( $intent ? "WHAT THIS REPLY SHOULD ADDRESS: {$intent}\n\n" : "\n" ) .
            "Rules:\n" .
            "- Address the customer by their first name.\n" .
            "- 2 to 4 short paragraphs, separated by a blank line.\n" .
            "- Reference relevant booking details naturally where helpful.\n" .
            "- Sign off as \"{$company['name']}\".\n" .
            "- Output ONLY the email body as plain text. No subject line, no markdown, no placeholders in brackets.";

        $reply = $this->call_api( $prompt, 700 );
        if ( is_wp_error( $reply ) ) {
            wp_send_json_error( array( 'message' => $reply->get_error_message() ) );
        }

        wp_send_json_success( array(
            'reply'   => trim( (string) $reply ),
            'subject' => sprintf( __( 'Regarding your booking %s', 'wp-travel-machine' ), $booking->booking_number ),
        ) );
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
