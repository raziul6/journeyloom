<?php
namespace JourneyLoom\AI;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class AIEngine {
    public function __construct() {
        add_action( 'wp_ajax_wptm_ai_recommend', array( $this, 'recommend' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_recommend', array( $this, 'recommend' ) );
        add_action( 'wp_ajax_wptm_ai_search', array( $this, 'smart_search' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_search', array( $this, 'smart_search' ) );
        add_action( 'wp_ajax_wptm_ai_itinerary', array( $this, 'generate_itinerary' ) );
        add_action( 'wp_ajax_wptm_ai_generate_trip', array( $this, 'generate_trip' ) );
        add_action( 'wp_ajax_wptm_ai_draft_reply', array( $this, 'draft_reply' ) );
        add_action( 'wp_ajax_wptm_ai_generate_style', array( $this, 'generate_style' ) );
        add_action( 'wp_ajax_wptm_ai_chat', array( $this, 'chat' ) );
        add_action( 'wp_ajax_nopriv_wptm_ai_chat', array( $this, 'chat' ) );
    }

    /**
     * Whether AI is configured at all (master switch on + an API key set),
     * regardless of licence tier.
     */
    private function ai_configured() {
        return (bool) get_option( 'wptm_enable_ai', false ) && ! empty( get_option( 'wptm_ai_api_key', '' ) );
    }

    /**
     * Whether a given AI feature may run on this site.
     *
     * Free tier unlocks natural-language search and the text chat assistant;
     * everything else (trip builder, recommendations, itinerary, replies) is Pro.
     * All features still require AI enabled + an API key.
     *
     * @param string $feature 'search' or 'chat' for free features; anything else = Pro.
     * @return bool
     */
    private function is_enabled( $feature = 'pro' ) {
        if ( ! $this->ai_configured() ) {
            return false;
        }
        if ( 'search' === $feature || 'chat' === $feature ) {
            return true;
        }
        return wptm_is_pro();
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
        $is_pro  = wptm_is_pro();

        /**
         * Filter the max number of AI requests allowed per visitor per minute.
         * Free sites get a tighter default since public endpoints are backed by
         * the owner's own paid API key.
         *
         * @param int  $max    Maximum requests per minute.
         * @param bool $is_pro Whether the site is running the Pro licence.
         */
        $per_min = (int) apply_filters( 'wptm_ai_rate_limit', $is_pro ? 10 : 6, $is_pro );
        $min_key = 'wptm_ai_rl_' . $bucket;
        $min_hits = (int) get_transient( $min_key );
        if ( $per_min > 0 && $min_hits >= $per_min ) {
            return false;
        }

        /**
         * Filter the max number of AI requests allowed per visitor per day.
         * A daily ceiling protects free sites from a bot draining their API
         * budget. Return 0 to disable the daily cap (the Pro default).
         *
         * @param int  $max    Maximum requests per day (0 = unlimited).
         * @param bool $is_pro Whether the site is running the Pro licence.
         */
        $per_day = (int) apply_filters( 'wptm_ai_daily_limit', $is_pro ? 0 : 150, $is_pro );
        // Date-scoped key so the counter resets each UTC day (a fixed TTL alone
        // would slide forward on every hit and never reset for active visitors).
        $day_key  = 'wptm_ai_rld_' . gmdate( 'Ymd' ) . '_' . $bucket;
        $day_hits = (int) get_transient( $day_key );
        if ( $per_day > 0 && $day_hits >= $per_day ) {
            return false;
        }

        set_transient( $min_key, $min_hits + 1, MINUTE_IN_SECONDS );
        if ( $per_day > 0 ) {
            set_transient( $day_key, $day_hits + 1, DAY_IN_SECONDS );
        }
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
            return new \WP_Error( 'wptm_ai_no_key', __( 'AI API key is not configured.', 'journeyloom' ) );
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
                    return new \WP_Error( 'wptm_ai_no_base_url', __( 'A Base URL is required for the custom AI provider.', 'journeyloom' ) );
                }
                if ( empty( $model ) ) {
                    return new \WP_Error( 'wptm_ai_no_model', __( 'A model name is required for the custom AI provider.', 'journeyloom' ) );
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
            /* translators: %d: HTTP status code returned by the AI provider. */
            return new \WP_Error( 'wptm_ai_http_error', $msg ?: sprintf( __( 'AI request failed (HTTP %d).', 'journeyloom' ), $code ) );
        }

        $text = $is_anthropic
            ? ( $body['content'][0]['text'] ?? '' )
            : ( $body['choices'][0]['message']['content'] ?? '' );

        if ( '' === trim( (string) $text ) ) {
            return new \WP_Error( 'wptm_ai_empty', __( 'The AI returned an empty response.', 'journeyloom' ) );
        }

        return $text;
    }

    public function recommend() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() ) wp_send_json_error( array( 'message' => 'AI not enabled.' ) );
        if ( ! $this->rate_limit_ok() ) wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'journeyloom' ) ), 429 );

        $prefs = sanitize_text_field( wp_unslash( $_POST['preferences'] ?? '' ) );
        $budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) );

        // Build a candidate pool of real trips AND hotels, each tagged with a
        // stable code (T<id> / H<id>) so the model returns IDs we can resolve to
        // actual bookable posts — not free-text titles that may not exist.
        $candidates = $this->recommend_candidates();
        if ( empty( $candidates['list'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No trips or hotels are available to recommend yet.', 'journeyloom' ) ) );
        }

        $prompt = "You are a travel advisor. A visitor describes what they want and an optional budget. "
            . "Pick the best matches ONLY from the catalog below and explain why each fits.\n\n"
            . "Preferences: '{$prefs}'\nBudget: '{$budget}'\n\n"
            . "Catalog (format: [CODE] Title — type, price, details):\n{$candidates['list']}\n\n"
            . "Return STRICT JSON: an array of up to 4 objects, each with keys: "
            . "\"id\" (the exact CODE, e.g. T12 or H34), "
            . "\"reason\" (one short sentence, max 18 words, addressed to the visitor), "
            . "\"match_score\" (integer 1-100). "
            . "Order best match first. Output JSON only, no prose.";

        $result = $this->call_api( $prompt );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $recs = $this->parse_recommendations( $result, $candidates['valid'] );
        if ( empty( $recs ) ) {
            wp_send_json_error( array( 'message' => __( 'No matching trips or hotels found. Try describing your trip differently.', 'journeyloom' ) ) );
        }

        wp_send_json_success( array(
            'html'  => $this->render_recommendation_cards( $recs ),
            'count' => count( $recs ),
        ) );
    }

    /**
     * Build the trip + hotel candidate pool fed to the recommender.
     *
     * @return array{list:string,valid:array<string,array{id:int,type:string}>}
     *               'list' is the prompt text; 'valid' maps each [CODE] to a real post.
     */
    private function recommend_candidates() {
        $sym   = get_option( 'wptm_currency_symbol', '$' );
        $lines = array();
        $valid = array();

        // Trips.
        $trips = get_posts( array(
            'post_type'      => 'wptm_trip',
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ) );
        foreach ( $trips as $t ) {
            $p        = get_post_meta( $t->ID, '_wptm_pricing', true );
            $price    = is_array( $p ) && ! empty( $p ) ? (float) $p[0]['price'] : 0;
            $duration = get_post_meta( $t->ID, '_wptm_duration', true );
            $unit     = get_post_meta( $t->ID, '_wptm_duration_unit', true ) ?: 'days';
            $dests    = get_the_terms( $t->ID, 'wptm_destination' );
            $dest     = ! is_wp_error( $dests ) && ! empty( $dests ) ? $dests[0]->name : '';
            $code     = 'T' . $t->ID;
            $lines[]  = "[{$code}] {$t->post_title} — trip, {$sym}{$price}, {$duration} {$unit}" . ( $dest ? ", {$dest}" : '' );
            $valid[ $code ] = array( 'id' => (int) $t->ID, 'type' => 'trip' );
        }

        // Hotels (with cheapest available room price, in one grouped query).
        $hotels = get_posts( array(
            'post_type'      => 'wptm_hotel',
            'posts_per_page' => 15,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ) );
        if ( $hotels ) {
            global $wpdb;
            $ids    = array_map( 'absint', wp_list_pluck( $hotels, 'ID' ) );
            $in     = implode( ',', $ids );
            $prices = array();
            if ( '' !== $in ) {
                // $in is composed solely of absint()'d IDs, so it is safe to inline.
                $rows = $wpdb->get_results( "SELECT hotel_id, MIN(price_per_night) AS p FROM {$wpdb->prefix}wptm_rooms WHERE status = 'available' AND hotel_id IN ({$in}) GROUP BY hotel_id", ARRAY_A );
                foreach ( (array) $rows as $row ) {
                    $prices[ (int) $row['hotel_id'] ] = (float) $row['p'];
                }
            }
            foreach ( $hotels as $h ) {
                $city    = get_post_meta( $h->ID, '_wptm_hotel_city', true );
                $country = get_post_meta( $h->ID, '_wptm_hotel_country', true );
                $loc     = trim( $city . ', ' . $country, ', ' );
                $stars   = (int) get_post_meta( $h->ID, '_wptm_star_rating', true );
                $price   = $prices[ $h->ID ] ?? 0;
                $code    = 'H' . $h->ID;
                $lines[] = "[{$code}] {$h->post_title} — hotel, {$sym}{$price}/night" . ( $stars ? ", {$stars}-star" : '' ) . ( $loc ? ", {$loc}" : '' );
                $valid[ $code ] = array( 'id' => (int) $h->ID, 'type' => 'hotel' );
            }
        }

        return array( 'list' => implode( "\n", $lines ), 'valid' => $valid );
    }

    /**
     * Parse the model's JSON reply into validated recommendations.
     *
     * Only codes present in $valid survive, so the model can never point a
     * visitor at a post that does not exist or is the wrong type.
     *
     * @param string $text  Raw model reply.
     * @param array  $valid Map of [CODE] => array{id,type} from the candidate pool.
     * @return array<int,array{id:int,type:string,reason:string,score:int}>
     */
    private function parse_recommendations( $text, $valid ) {
        $start = strpos( $text, '[' );
        $end   = strrpos( $text, ']' );
        if ( false === $start || false === $end || $end <= $start ) {
            return array();
        }
        $arr = json_decode( substr( $text, $start, $end - $start + 1 ), true );
        if ( ! is_array( $arr ) ) {
            return array();
        }

        $out  = array();
        $seen = array();
        foreach ( $arr as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            // Tolerate "T12", "[T12]", "t12" etc.
            $code = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) ( $row['id'] ?? '' ) ) );
            if ( ! isset( $valid[ $code ] ) || isset( $seen[ $code ] ) ) {
                continue;
            }
            $seen[ $code ] = true;
            $out[] = array(
                'id'     => $valid[ $code ]['id'],
                'type'   => $valid[ $code ]['type'],
                'reason' => sanitize_text_field( (string) ( $row['reason'] ?? '' ) ),
                'score'  => isset( $row['match_score'] ) ? max( 1, min( 100, (int) $row['match_score'] ) ) : 0,
            );
            if ( count( $out ) >= 4 ) {
                break;
            }
        }
        return $out;
    }

    /**
     * Render validated recommendations as real trip/hotel cards, each wrapped
     * with the AI's "why this fits" reason and match score.
     *
     * @param array $recs Output of parse_recommendations().
     * @return string Card HTML (safe to inject; the AI-controlled text is escaped).
     */
    private function render_recommendation_cards( $recs ) {
        global $post;
        $original = $post;
        $html     = '';

        foreach ( $recs as $rec ) {
            $p = get_post( $rec['id'] );
            if ( ! $p || 'publish' !== $p->post_status ) {
                continue;
            }
            $partial = ( 'hotel' === $rec['type'] )
                ? WPTM_PLUGIN_DIR . 'templates/partials/hotel-card.php'
                : WPTM_PLUGIN_DIR . 'templates/partials/trip-card.php';
            if ( ! file_exists( $partial ) ) {
                continue;
            }

            $post = $p;
            setup_postdata( $post );
            ob_start();
            include $partial;
            $card = ob_get_clean();

            $badge  = $rec['score']
                ? '<span class="wptm-ai-rec__score">' . esc_html( $rec['score'] . '% ' . __( 'match', 'journeyloom' ) ) . '</span>'
                : '';
            $reason = $rec['reason']
                ? '<p class="wptm-ai-rec__reason">' . esc_html( $rec['reason'] ) . '</p>'
                : '';

            // The "why this fits" header only appears when the model gave a
            // reason/score (the recommender form). In chat the card stands alone.
            $head = ( $rec['reason'] || $rec['score'] )
                ? '<div class="wptm-ai-rec__head"><span class="wptm-ai-rec__why">' . ( $rec['reason'] ? '✨ ' . esc_html__( 'Why this fits', 'journeyloom' ) : '' ) . '</span>' . $badge . '</div>'
                : '';

            $html .= '<div class="wptm-ai-rec">' . $head . $reason . $card . '</div>';
        }

        $post = $original;
        wp_reset_postdata();
        return $html;
    }

    /**
     * Generate cohesive card style presets from a free-text "vibe" for the
     * block / Elementor editors. The model only returns values for the existing
     * style attributes (colors, radius, gap) — never raw CSS — so output is safe.
     */
    public function generate_style() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        if ( ! $this->is_enabled() || ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'AI style generation is a Pro feature.', 'journeyloom' ) ) );
        }
        if ( ! $this->rate_limit_ok() ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'journeyloom' ) ), 429 );
        }

        $vibe = sanitize_text_field( wp_unslash( $_POST['vibe'] ?? '' ) );
        if ( '' === $vibe ) {
            wp_send_json_error( array( 'message' => __( 'Describe the style you want (e.g. "luxury beach").', 'journeyloom' ) ) );
        }

        $prompt = "You are a senior UI designer creating card styles for a travel website. "
            . "The desired vibe is: '{$vibe}'.\n"
            . "Design 3 distinct, cohesive, accessible style presets for trip/hotel cards shown on a WHITE background. "
            . "Each preset has: accent (a vivid brand colour used for price & buttons), "
            . "titleColor (near-black, high contrast), textColor (muted grey body text, still readable), "
            . "btnBg (button background — usually equal to accent), btnColor (button text, white or near-white), "
            . "cardRadius (integer 0-32, px) and gap (integer 8-48, px). "
            . "Return STRICT JSON: an array of exactly 3 objects with keys "
            . "name, accent, titleColor, textColor, btnBg, btnColor, cardRadius, gap. "
            . "All colours as #RRGGBB hex. Output JSON only, no prose.";

        $result = $this->call_api( $prompt, 700 );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $presets = $this->parse_style_presets( $result );
        if ( empty( $presets ) ) {
            wp_send_json_error( array( 'message' => __( 'Could not generate a style. Try a different description.', 'journeyloom' ) ) );
        }
        wp_send_json_success( array( 'presets' => $presets ) );
    }

    /**
     * Validate the model's style JSON into safe, bounded presets.
     *
     * @param string $text Raw model reply.
     * @return array<int,array<string,mixed>>
     */
    private function parse_style_presets( $text ) {
        $start = strpos( $text, '[' );
        $end   = strrpos( $text, ']' );
        if ( false === $start || false === $end || $end <= $start ) {
            return array();
        }
        $arr = json_decode( substr( $text, $start, $end - $start + 1 ), true );
        if ( ! is_array( $arr ) ) {
            return array();
        }

        $hex = function ( $v, $fallback ) {
            $v = is_string( $v ) ? trim( $v ) : '';
            return preg_match( '/^#[0-9a-fA-F]{6}$/', $v ) ? strtolower( $v ) : $fallback;
        };

        $out = array();
        foreach ( $arr as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            $accent = $hex( $row['accent'] ?? '', '#fd4621' );
            $out[]  = array(
                'name'       => sanitize_text_field( (string) ( $row['name'] ?? __( 'Style', 'journeyloom' ) ) ),
                'accent'     => $accent,
                'titleColor' => $hex( $row['titleColor'] ?? '', '#1a1410' ),
                'textColor'  => $hex( $row['textColor'] ?? '', '#44403c' ),
                'btnBg'      => $hex( $row['btnBg'] ?? '', $accent ),
                'btnColor'   => $hex( $row['btnColor'] ?? '', '#ffffff' ),
                'cardRadius' => max( 0, min( 40, (int) ( $row['cardRadius'] ?? 18 ) ) ),
                'gap'        => max( 0, min( 80, (int) ( $row['gap'] ?? 24 ) ) ),
            );
            if ( count( $out ) >= 3 ) {
                break;
            }
        }
        return $out;
    }

    public function smart_search() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        // Natural-language search is available on the free tier.
        if ( ! $this->is_enabled( 'search' ) ) {
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
            wp_send_json_error( array( 'message' => __( 'AI is not available.', 'journeyloom' ) ) );
        }
        if ( ! $this->rate_limit_ok() ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'journeyloom' ) ), 429 );
        }

        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $dest  = sanitize_text_field( wp_unslash( $_POST['destination'] ?? '' ) );
        $days  = max( 1, min( 30, absint( $_POST['days'] ?? 5 ) ) );
        $style = sanitize_text_field( wp_unslash( $_POST['style'] ?? 'adventure' ) );
        $budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? 'mid-range' ) );
        $audience = sanitize_text_field( wp_unslash( $_POST['audience'] ?? '' ) );

        $subject = $dest ?: $title;
        if ( '' === trim( $subject ) ) {
            wp_send_json_error( array( 'message' => __( 'Add a trip title or destination first.', 'journeyloom' ) ) );
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
            wp_send_json_error( array( 'message' => __( 'The AI response could not be read. Please try again.', 'journeyloom' ) ) );
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
            wp_send_json_error( array( 'message' => __( 'AI is not available.', 'journeyloom' ) ) );
        }
        if ( ! $this->rate_limit_ok() ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'journeyloom' ) ), 429 );
        }

        $id      = absint( $_POST['booking_id'] ?? 0 );
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $id );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'journeyloom' ) ) );
        }

        $intent = sanitize_textarea_field( wp_unslash( $_POST['intent'] ?? '' ) );
        $tone   = sanitize_text_field( wp_unslash( $_POST['tone'] ?? 'friendly' ) );
        $allowed_tones = array( 'friendly', 'professional', 'apologetic', 'enthusiastic' );
        if ( ! in_array( $tone, $allowed_tones, true ) ) {
            $tone = 'friendly';
        }

        $sym     = get_option( 'wptm_currency_symbol', '$' );
        $company = \JourneyLoom\Booking\Invoice::business();
        $item    = get_the_title( $booking->item_id ) ?: __( 'their booking', 'journeyloom' );

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
            /* translators: %s: booking reference number. */
            'subject' => sprintf( __( 'Regarding your booking %s', 'journeyloom' ), $booking->booking_number ),
        ) );
    }

    public function chat() {
        check_ajax_referer( 'wptm_ai_nonce', 'nonce' );
        // The text chat assistant is available on the free tier; the inline
        // bookable-card suggestions below are a Pro-only upsell.
        if ( ! $this->is_enabled( 'chat' ) ) wp_send_json_error( array( 'message' => 'AI chat not available.' ) );
        if ( ! $this->rate_limit_ok() ) wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'journeyloom' ) ), 429 );

        $message = sanitize_text_field( wp_unslash( $_POST['message'] ?? '' ) );
        if ( '' === $message ) {
            wp_send_json_error( array( 'message' => __( 'Please type a message.', 'journeyloom' ) ) );
        }

        // Same trip + hotel pool the recommender uses, so the assistant can
        // reference (Pro: suggest) real, bookable items by their stable code.
        $candidates = $this->recommend_candidates();

        if ( wptm_is_pro() ) {
            $prompt = "You are a friendly, concise travel assistant for a booking website. "
                . "Reply to the visitor in 1-3 short sentences. "
                . "ONLY if the visitor is browsing for or interested in booking a trip or hotel, also suggest "
                . "up to 3 matching items from the catalog below by their CODE. "
                . "For pure questions (visa, weather, general advice), recommend nothing.\n\n"
                . "Catalog (format: [CODE] Title — type, price, details):\n{$candidates['list']}\n\n"
                . "Visitor: {$message}\n\n"
                . "Respond with STRICT JSON only: {\"reply\": \"your message\", \"recommend\": [\"CODE\", ...]}. "
                . "Use an empty array when nothing relevant fits.";

            $result = $this->call_api( $prompt, 500 );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            }

            $data = $this->extract_json( $result );
            if ( is_array( $data ) ) {
                $reply = trim( (string) ( $data['reply'] ?? '' ) );
                $recs  = $this->codes_to_recs( $data['recommend'] ?? array(), $candidates['valid'] );
                $cards = $recs ? $this->render_recommendation_cards( $recs ) : '';
            } else {
                // Model ignored the JSON contract — fall back to its raw text reply.
                $reply = trim( wp_strip_all_tags( (string) $result ) );
                $cards = '';
            }
        } else {
            // Free tier: conversational text only. The model may mention trips by
            // name for context, but no bookable cards are rendered (Pro upsell).
            $prompt = "You are a friendly, concise travel assistant for a booking website. "
                . "Answer the visitor's message helpfully in 1-3 short sentences. "
                . "You may mention relevant trips or hotels by name from this list, but do not invent details.\n\n"
                . "Trips & hotels:\n{$candidates['list']}\n\n"
                . "Visitor: {$message}";

            $result = $this->call_api( $prompt, 400 );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            }
            $reply = trim( wp_strip_all_tags( (string) $result ) );
            $cards = '';
        }

        if ( '' === $reply && '' === $cards ) {
            wp_send_json_error( array( 'message' => __( 'Sorry, I couldn\'t process that. Please try again.', 'journeyloom' ) ) );
        }

        wp_send_json_success( array( 'reply' => $reply, 'cards' => $cards ) );
    }

    /**
     * Map catalog codes (from the chat assistant) to renderable recommendations.
     *
     * Unlike parse_recommendations(), these carry no reason/score — the chat
     * reply already provides the conversational context — so the cards render
     * on their own. Codes are validated against the real pool.
     *
     * @param mixed $codes Array of code strings (or {id}/{code} objects).
     * @param array $valid Map of [CODE] => array{id,type}.
     * @return array<int,array{id:int,type:string,reason:string,score:int}>
     */
    private function codes_to_recs( $codes, $valid ) {
        $out  = array();
        $seen = array();
        foreach ( (array) $codes as $raw ) {
            if ( is_array( $raw ) ) {
                $raw = $raw['id'] ?? $raw['code'] ?? reset( $raw );
            }
            $code = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $raw ) );
            if ( ! isset( $valid[ $code ] ) || isset( $seen[ $code ] ) ) {
                continue;
            }
            $seen[ $code ] = true;
            $out[] = array(
                'id'     => $valid[ $code ]['id'],
                'type'   => $valid[ $code ]['type'],
                'reason' => '',
                'score'  => 0,
            );
            if ( count( $out ) >= 3 ) {
                break;
            }
        }
        return $out;
    }
}
