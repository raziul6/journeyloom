<?php
/**
 * Global helper functions.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Full list of world currencies — code => array( name, symbol ).
 *
 * Filterable via 'wptm_currencies' so devs can add/remove entries.
 *
 * @return array
 */
function wptm_get_currencies() {
    $currencies = array(
        'AED' => array( 'United Arab Emirates Dirham', 'د.إ' ),
        'AFN' => array( 'Afghan Afghani', '؋' ),
        'ALL' => array( 'Albanian Lek', 'L' ),
        'AMD' => array( 'Armenian Dram', '֏' ),
        'ANG' => array( 'Netherlands Antillean Guilder', 'ƒ' ),
        'AOA' => array( 'Angolan Kwanza', 'Kz' ),
        'ARS' => array( 'Argentine Peso', '$' ),
        'AUD' => array( 'Australian Dollar', '$' ),
        'AWG' => array( 'Aruban Florin', 'ƒ' ),
        'AZN' => array( 'Azerbaijani Manat', '₼' ),
        'BAM' => array( 'Bosnia-Herzegovina Convertible Mark', 'KM' ),
        'BBD' => array( 'Barbadian Dollar', '$' ),
        'BDT' => array( 'Bangladeshi Taka', '৳' ),
        'BGN' => array( 'Bulgarian Lev', 'лв' ),
        'BHD' => array( 'Bahraini Dinar', '.د.ب' ),
        'BIF' => array( 'Burundian Franc', 'FBu' ),
        'BMD' => array( 'Bermudan Dollar', '$' ),
        'BND' => array( 'Brunei Dollar', '$' ),
        'BOB' => array( 'Bolivian Boliviano', 'Bs.' ),
        'BRL' => array( 'Brazilian Real', 'R$' ),
        'BSD' => array( 'Bahamian Dollar', '$' ),
        'BTN' => array( 'Bhutanese Ngultrum', 'Nu.' ),
        'BWP' => array( 'Botswanan Pula', 'P' ),
        'BYN' => array( 'Belarusian Ruble', 'Br' ),
        'BZD' => array( 'Belize Dollar', 'BZ$' ),
        'CAD' => array( 'Canadian Dollar', '$' ),
        'CDF' => array( 'Congolese Franc', 'FC' ),
        'CHF' => array( 'Swiss Franc', 'CHF' ),
        'CLP' => array( 'Chilean Peso', '$' ),
        'CNY' => array( 'Chinese Yuan', '¥' ),
        'COP' => array( 'Colombian Peso', '$' ),
        'CRC' => array( 'Costa Rican Colón', '₡' ),
        'CUP' => array( 'Cuban Peso', '$' ),
        'CVE' => array( 'Cape Verdean Escudo', '$' ),
        'CZK' => array( 'Czech Koruna', 'Kč' ),
        'DJF' => array( 'Djiboutian Franc', 'Fdj' ),
        'DKK' => array( 'Danish Krone', 'kr' ),
        'DOP' => array( 'Dominican Peso', 'RD$' ),
        'DZD' => array( 'Algerian Dinar', 'دج' ),
        'EGP' => array( 'Egyptian Pound', 'E£' ),
        'ERN' => array( 'Eritrean Nakfa', 'Nfk' ),
        'ETB' => array( 'Ethiopian Birr', 'Br' ),
        'EUR' => array( 'Euro', '€' ),
        'FJD' => array( 'Fijian Dollar', '$' ),
        'FKP' => array( 'Falkland Islands Pound', '£' ),
        'GBP' => array( 'British Pound Sterling', '£' ),
        'GEL' => array( 'Georgian Lari', '₾' ),
        'GHS' => array( 'Ghanaian Cedi', '₵' ),
        'GIP' => array( 'Gibraltar Pound', '£' ),
        'GMD' => array( 'Gambian Dalasi', 'D' ),
        'GNF' => array( 'Guinean Franc', 'FG' ),
        'GTQ' => array( 'Guatemalan Quetzal', 'Q' ),
        'GYD' => array( 'Guyanaese Dollar', '$' ),
        'HKD' => array( 'Hong Kong Dollar', '$' ),
        'HNL' => array( 'Honduran Lempira', 'L' ),
        'HRK' => array( 'Croatian Kuna', 'kn' ),
        'HTG' => array( 'Haitian Gourde', 'G' ),
        'HUF' => array( 'Hungarian Forint', 'Ft' ),
        'IDR' => array( 'Indonesian Rupiah', 'Rp' ),
        'ILS' => array( 'Israeli New Shekel', '₪' ),
        'INR' => array( 'Indian Rupee', '₹' ),
        'IQD' => array( 'Iraqi Dinar', 'ع.د' ),
        'IRR' => array( 'Iranian Rial', '﷼' ),
        'ISK' => array( 'Icelandic Króna', 'kr' ),
        'JMD' => array( 'Jamaican Dollar', 'J$' ),
        'JOD' => array( 'Jordanian Dinar', 'د.ا' ),
        'JPY' => array( 'Japanese Yen', '¥' ),
        'KES' => array( 'Kenyan Shilling', 'KSh' ),
        'KGS' => array( 'Kyrgystani Som', 'с' ),
        'KHR' => array( 'Cambodian Riel', '៛' ),
        'KMF' => array( 'Comorian Franc', 'CF' ),
        'KRW' => array( 'South Korean Won', '₩' ),
        'KWD' => array( 'Kuwaiti Dinar', 'د.ك' ),
        'KYD' => array( 'Cayman Islands Dollar', '$' ),
        'KZT' => array( 'Kazakhstani Tenge', '₸' ),
        'LAK' => array( 'Laotian Kip', '₭' ),
        'LBP' => array( 'Lebanese Pound', 'ل.ل' ),
        'LKR' => array( 'Sri Lankan Rupee', 'Rs' ),
        'LRD' => array( 'Liberian Dollar', '$' ),
        'LSL' => array( 'Lesotho Loti', 'L' ),
        'LYD' => array( 'Libyan Dinar', 'ل.د' ),
        'MAD' => array( 'Moroccan Dirham', 'د.م.' ),
        'MDL' => array( 'Moldovan Leu', 'L' ),
        'MGA' => array( 'Malagasy Ariary', 'Ar' ),
        'MKD' => array( 'Macedonian Denar', 'ден' ),
        'MMK' => array( 'Myanmar Kyat', 'K' ),
        'MNT' => array( 'Mongolian Tugrik', '₮' ),
        'MOP' => array( 'Macanese Pataca', 'MOP$' ),
        'MRU' => array( 'Mauritanian Ouguiya', 'UM' ),
        'MUR' => array( 'Mauritian Rupee', '₨' ),
        'MVR' => array( 'Maldivian Rufiyaa', 'Rf' ),
        'MWK' => array( 'Malawian Kwacha', 'MK' ),
        'MXN' => array( 'Mexican Peso', '$' ),
        'MYR' => array( 'Malaysian Ringgit', 'RM' ),
        'MZN' => array( 'Mozambican Metical', 'MT' ),
        'NAD' => array( 'Namibian Dollar', '$' ),
        'NGN' => array( 'Nigerian Naira', '₦' ),
        'NIO' => array( 'Nicaraguan Córdoba', 'C$' ),
        'NOK' => array( 'Norwegian Krone', 'kr' ),
        'NPR' => array( 'Nepalese Rupee', '₨' ),
        'NZD' => array( 'New Zealand Dollar', '$' ),
        'OMR' => array( 'Omani Rial', 'ر.ع.' ),
        'PAB' => array( 'Panamanian Balboa', 'B/.' ),
        'PEN' => array( 'Peruvian Sol', 'S/' ),
        'PGK' => array( 'Papua New Guinean Kina', 'K' ),
        'PHP' => array( 'Philippine Peso', '₱' ),
        'PKR' => array( 'Pakistani Rupee', '₨' ),
        'PLN' => array( 'Polish Zloty', 'zł' ),
        'PYG' => array( 'Paraguayan Guarani', '₲' ),
        'QAR' => array( 'Qatari Rial', 'ر.ق' ),
        'RON' => array( 'Romanian Leu', 'lei' ),
        'RSD' => array( 'Serbian Dinar', 'дин.' ),
        'RUB' => array( 'Russian Ruble', '₽' ),
        'RWF' => array( 'Rwandan Franc', 'FRw' ),
        'SAR' => array( 'Saudi Riyal', 'ر.س' ),
        'SBD' => array( 'Solomon Islands Dollar', '$' ),
        'SCR' => array( 'Seychellois Rupee', '₨' ),
        'SDG' => array( 'Sudanese Pound', 'ج.س.' ),
        'SEK' => array( 'Swedish Krona', 'kr' ),
        'SGD' => array( 'Singapore Dollar', '$' ),
        'SHP' => array( 'Saint Helena Pound', '£' ),
        'SLL' => array( 'Sierra Leonean Leone', 'Le' ),
        'SOS' => array( 'Somali Shilling', 'S' ),
        'SRD' => array( 'Surinamese Dollar', '$' ),
        'SSP' => array( 'South Sudanese Pound', '£' ),
        'STN' => array( 'São Tomé & Príncipe Dobra', 'Db' ),
        'SYP' => array( 'Syrian Pound', '£' ),
        'SZL' => array( 'Swazi Lilangeni', 'L' ),
        'THB' => array( 'Thai Baht', '฿' ),
        'TJS' => array( 'Tajikistani Somoni', 'ЅМ' ),
        'TMT' => array( 'Turkmenistani Manat', 'm' ),
        'TND' => array( 'Tunisian Dinar', 'د.ت' ),
        'TOP' => array( 'Tongan Paʻanga', 'T$' ),
        'TRY' => array( 'Turkish Lira', '₺' ),
        'TTD' => array( 'Trinidad & Tobago Dollar', 'TT$' ),
        'TWD' => array( 'New Taiwan Dollar', 'NT$' ),
        'TZS' => array( 'Tanzanian Shilling', 'TSh' ),
        'UAH' => array( 'Ukrainian Hryvnia', '₴' ),
        'UGX' => array( 'Ugandan Shilling', 'USh' ),
        'USD' => array( 'US Dollar', '$' ),
        'UYU' => array( 'Uruguayan Peso', '$U' ),
        'UZS' => array( 'Uzbekistani Som', "so'm" ),
        'VES' => array( 'Venezuelan Bolívar', 'Bs.' ),
        'VND' => array( 'Vietnamese Dong', '₫' ),
        'VUV' => array( 'Vanuatu Vatu', 'VT' ),
        'WST' => array( 'Samoan Tala', 'WS$' ),
        'XAF' => array( 'Central African CFA Franc', 'FCFA' ),
        'XCD' => array( 'East Caribbean Dollar', '$' ),
        'XOF' => array( 'West African CFA Franc', 'CFA' ),
        'XPF' => array( 'CFP Franc', '₣' ),
        'YER' => array( 'Yemeni Rial', '﷼' ),
        'ZAR' => array( 'South African Rand', 'R' ),
        'ZMW' => array( 'Zambian Kwacha', 'ZK' ),
        'ZWL' => array( 'Zimbabwean Dollar', 'Z$' ),
    );

    /**
     * Filter the list of selectable currencies.
     *
     * @param array $currencies Map of ISO code => array( name, symbol ).
     */
    return apply_filters( 'wptm_currencies', $currencies );
}

/**
 * Format a price with the configured currency symbol.
 *
 * @param float  $amount The amount to format.
 * @param int    $decimals Number of decimal places (default 2).
 * @return string Formatted price string.
 */
function wptm_format_price( $amount, $decimals = 2 ) {
    $sym = get_option( 'wptm_currency_symbol', '$' );
    $pos = get_option( 'wptm_currency_position', 'before' );
    $formatted = number_format( floatval( $amount ), $decimals );
    return 'before' === $pos ? $sym . $formatted : $formatted . $sym;
}

/**
 * Get a plugin option with a default value.
 *
 * @param string $key Option key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function wptm_get_option( $key, $default = '' ) {
    $value = get_option( $key, $default );
    return ( false === $value || '' === $value ) ? $default : $value;
}

/**
 * Check if a feature is enabled.
 *
 * @param string $feature Feature slug (e.g. 'wishlist', 'compare', 'reviews', 'ai').
 * @return bool
 */
function wptm_is_feature_enabled( $feature ) {
    return (bool) get_option( 'wptm_enable_' . $feature, false );
}

/**
 * Get the URL for a WPTM system page.
 *
 * @param string $page_type Page type key (e.g. 'search', 'checkout', 'confirmation', 'destinations').
 * @return string Page URL or empty string.
 */
function wptm_get_page_url( $page_type ) {
    $page_id = get_option( 'wptm_page_' . $page_type, 0 );
    if ( $page_id ) {
        return get_permalink( $page_id );
    }
    return '';
}

/**
 * Get all system page IDs.
 *
 * @return array Associative array of page_type => page_id.
 */
function wptm_get_system_pages() {
    return array(
        'search'           => (int) get_option( 'wptm_page_search', 0 ),
        'checkout'         => (int) get_option( 'wptm_page_checkout', 0 ),
        'confirmation'     => (int) get_option( 'wptm_page_confirmation', 0 ),
        'destinations'     => (int) get_option( 'wptm_page_destinations', 0 ),
        'activities'       => (int) get_option( 'wptm_page_activities', 0 ),
        'trip_types'       => (int) get_option( 'wptm_page_trip_types', 0 ),
        'difficulties'     => (int) get_option( 'wptm_page_difficulties', 0 ),
        'hotel_types'      => (int) get_option( 'wptm_page_hotel_types', 0 ),
        'hotel_facilities' => (int) get_option( 'wptm_page_hotel_facilities', 0 ),
        'trips'            => (int) get_option( 'wptm_page_trips', 0 ),
        'hotels'           => (int) get_option( 'wptm_page_hotels', 0 ),
        'wishlist'         => (int) get_option( 'wptm_page_wishlist', 0 ),
        'cart'             => (int) get_option( 'wptm_page_cart', 0 ),
        'my_bookings'      => (int) get_option( 'wptm_page_my_bookings', 0 ),
    );
}

/**
 * Opaque access key for a booking's confirmation/order link.
 *
 * Derived with wp_hash() from immutable booking fields (no extra storage), so
 * booking details are only visible to someone who has the link from the
 * checkout redirect or confirmation email — not to anyone guessing ids.
 *
 * @param object $booking Booking row (id, booking_number, customer_email).
 * @return string
 */
function wptm_booking_key( $booking ) {
    if ( ! $booking || empty( $booking->id ) ) {
        return '';
    }
    $data = (int) $booking->id . '|' . (string) ( $booking->booking_number ?? '' ) . '|' . strtolower( (string) ( $booking->customer_email ?? '' ) );
    return substr( wp_hash( 'wptm_booking_key|' . $data ), 0, 20 );
}

/**
 * Confirmation/order page URL for a booking, including its access key.
 *
 * @param object $booking Booking row.
 * @return string
 */
function wptm_booking_confirmation_url( $booking ) {
    $url = wptm_get_page_url( 'confirmation' );
    if ( ! $url ) {
        $url = home_url( '/booking-confirmation/' );
    }
    return add_query_arg(
        array(
            'booking' => (int) ( $booking->id ?? 0 ),
            'key'     => rawurlencode( wptm_booking_key( $booking ) ),
        ),
        $url
    );
}

/**
 * Whether the current visitor is allowed to see a booking's details.
 *
 * True when the request carries the booking's access key ({@see
 * wptm_booking_key()}), when the booking belongs to the logged-in user (by
 * user id or email), or when the user can manage the site.
 *
 * @param object $booking Booking row.
 * @return bool
 */
function wptm_current_user_can_view_booking( $booking ) {
    if ( ! $booking ) {
        return false;
    }
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only authorization token from the confirmation link, compared in constant time below.
    $key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
    $expected = wptm_booking_key( $booking );
    if ( '' !== $key && '' !== $expected && hash_equals( $expected, $key ) ) {
        return true;
    }

    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        if ( (int) ( $booking->user_id ?? 0 ) > 0 && (int) $booking->user_id === (int) $user->ID ) {
            return true;
        }
        if ( ! empty( $booking->customer_email ) && strtolower( $booking->customer_email ) === strtolower( (string) $user->user_email ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Locate a template file, preferring a theme override.
 *
 * Themes can override any template by placing a file at
 * `your-theme/journeyloom/<name>` (e.g. journeyloom/single-trip.php
 * or journeyloom/partials/booking-form.php).
 *
 * @param string $name Template filename relative to templates/.
 * @return string Absolute path to the template file (theme override or plugin default).
 */
function wptm_locate_template( $name ) {
    $theme = locate_template( 'journeyloom/' . $name );
    $file  = $theme ? $theme : WPTM_PLUGIN_DIR . 'templates/' . $name;

    /**
     * Filter the resolved template path.
     *
     * @param string $file Absolute path to the template that will be loaded.
     * @param string $name Template name relative to templates/.
     */
    return apply_filters( 'wptm_locate_template', $file, $name );
}

/**
 * Load a template / partial from the plugin or a theme override.
 *
 * @param string $name Template filename relative to templates/.
 * @param array  $args Variables to extract into the template scope.
 */
function wptm_get_template( $name, $args = array() ) {
    $file = wptm_locate_template( $name );
    if ( ! file_exists( $file ) ) {
        return;
    }

    /**
     * Filter the variables passed into a template before it renders.
     *
     * @param array  $args Template variables.
     * @param string $name Template name.
     */
    $args = apply_filters( 'wptm_template_args', $args, $name );
    if ( ! empty( $args ) ) {
        extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
    }

    include $file;
}

/**
 * Alias of wptm_get_template() — reads more naturally for partials.
 *
 * @param string $name Partial filename relative to templates/.
 * @param array  $args Variables to extract into the partial scope.
 */
function wptm_get_template_part( $name, $args = array() ) {
    wptm_get_template( $name, $args );
}

/**
 * Whether a URL's host is an allowed map-embed provider.
 *
 * @param string $url URL to check.
 * @return bool
 */
function wptm_is_allowed_map_host( $url ) {
    $host = wp_parse_url( $url, PHP_URL_HOST );
    if ( ! $host ) {
        return false;
    }
    $host = strtolower( $host );
    /**
     * Filter the allowed host suffixes for map-embed iframes.
     *
     * @param array $hosts Base domains permitted in a map embed src.
     */
    $allowed = apply_filters( 'wptm_allowed_map_hosts', array(
        'google.com', 'openstreetmap.org', 'mapbox.com', 'bing.com', 'maps.app.goo.gl',
    ) );
    foreach ( $allowed as $base ) {
        if ( $host === $base || substr( $host, -strlen( '.' . $base ) ) === '.' . $base ) {
            return true;
        }
    }
    return false;
}

/**
 * Build a safe, responsive map iframe from a validated embed URL.
 *
 * @param string $src   The map embed URL (https, allowed host).
 * @param string $title Accessible iframe title.
 * @return string Iframe HTML, or empty string if the URL is not allowed.
 */
function wptm_map_embed_iframe( $src, $title = '' ) {
    $src = trim( (string) $src );
    if ( '' === $src || 0 !== stripos( $src, 'https://' ) || ! wptm_is_allowed_map_host( $src ) ) {
        return '';
    }
    return sprintf(
        '<iframe src="%s" title="%s" width="100%%" height="340" style="border:0;width:100%%;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>',
        esc_url( $src ),
        esc_attr( '' !== $title ? $title : __( 'Map', 'byteflows-travel-hotel-booking' ) )
    );
}

/**
 * Sanitize a pasted map embed for storage.
 *
 * Accepts a full <iframe> snippet (Google Maps / OpenStreetMap "Embed a map")
 * or a bare URL. Extracts the src, validates scheme + host, and returns a clean
 * iframe built from our own template (never the pasted attributes). Returns an
 * empty string if the embed is missing or not from an allowed provider.
 *
 * @param string $raw   Raw textarea value.
 * @param string $title Accessible iframe title.
 * @return string
 */
function wptm_sanitize_map_embed( $raw, $title = '' ) {
    $raw = trim( (string) $raw );
    if ( '' === $raw ) {
        return '';
    }
    if ( preg_match( '/src\s*=\s*["\']([^"\']+)["\']/i', $raw, $m ) ) {
        $src = $m[1];
    } else {
        $src = $raw; // Allow pasting just the URL.
    }
    $src = esc_url_raw( html_entity_decode( trim( $src ), ENT_QUOTES ) );
    return wptm_map_embed_iframe( $src, $title );
}

/**
 * Normalise a "list" meta value to an array of non-empty strings.
 *
 * Supports the new repeater format (array) and the legacy textarea format
 * (newline-separated string), so old content keeps rendering.
 *
 * @param mixed $value Stored meta value.
 * @return string[]
 */
function wptm_to_list( $value ) {
    if ( is_array( $value ) ) {
        $items = $value;
    } elseif ( is_string( $value ) && '' !== $value ) {
        $items = preg_split( '/\r\n|\r|\n/', $value );
    } else {
        $items = array();
    }
    $items = array_map( 'trim', array_map( 'strval', $items ) );
    return array_values( array_filter( $items, function( $v ) { return '' !== $v; } ) );
}

/**
 * Enquiry form field definitions.
 *
 * Returns the admin-configured fields, or a sensible default set when none have
 * been saved yet. Each field: array( label, type, required, options ).
 *
 * @return array
 */
function wptm_enquiry_fields() {
    $fields = get_option( 'wptm_enquiry_fields', null );
    if ( ! is_array( $fields ) || empty( $fields ) ) {
        $fields = array(
            array( 'label' => __( 'Name', 'byteflows-travel-hotel-booking' ),    'type' => 'text',     'required' => 1, 'options' => '' ),
            array( 'label' => __( 'Email', 'byteflows-travel-hotel-booking' ),   'type' => 'email',    'required' => 1, 'options' => '' ),
            array( 'label' => __( 'Phone', 'byteflows-travel-hotel-booking' ),   'type' => 'tel',      'required' => 0, 'options' => '' ),
            array( 'label' => __( 'Message', 'byteflows-travel-hotel-booking' ), 'type' => 'textarea', 'required' => 1, 'options' => '' ),
        );
    }
    return $fields;
}

/**
 * Active payment methods for the checkout / booking form.
 *
 * Reads the enabled gateways from the Payment module so only configured methods
 * are offered, and decorates each with a default icon/description. Always falls
 * back to manual bank transfer so the checkout is never left without a method.
 *
 * @return array<int,array{id:string,title:string,desc:string,icon:string}>
 */
function wptm_payment_methods() {
    $plugin  = \JourneyLoom\Plugin::get_instance();
    $payment = $plugin ? $plugin->get_module( 'payment' ) : null;
    $active  = ( $payment && method_exists( $payment, 'get_active_gateways' ) ) ? $payment->get_active_gateways() : array();

    // Default presentation per gateway id (used when the gateway has none).
    // Add-on gateways can supply their own via the 'wptm_payment_methods' filter.
    $defaults = array(
        'manual' => array( 'icon' => 'bank', 'desc' => __( 'Pay via bank transfer. Your booking is confirmed once we verify the payment.', 'byteflows-travel-hotel-booking' ) ),
    );

    $methods = array();
    foreach ( $active as $id => $gw ) {
        $desc = $gw->get_description();
        if ( '' === $desc && isset( $defaults[ $id ]['desc'] ) ) {
            $desc = $defaults[ $id ]['desc'];
        }
        $methods[] = array(
            'id'    => $id,
            'title' => $gw->get_title(),
            'desc'  => $desc,
            'icon'  => isset( $defaults[ $id ]['icon'] ) ? $defaults[ $id ]['icon'] : 'wallet',
        );
    }

    if ( empty( $methods ) ) {
        $methods[] = array(
            'id'    => 'manual',
            'title' => __( 'Bank Transfer', 'byteflows-travel-hotel-booking' ),
            'desc'  => $defaults['manual']['desc'],
            'icon'  => 'bank',
        );
    }

    /**
     * Filter the payment methods shown on the checkout / booking form.
     *
     * @param array $methods List of method definitions.
     */
    return apply_filters( 'wptm_payment_methods', $methods );
}

/**
 * Inline SVG icon used by a payment method card.
 *
 * @param string $key Icon key (bank|card|paypal|wallet).
 * @return string Safe inline SVG markup.
 */
function wptm_payment_icon( $key ) {
    $icons = array(
        'card'   => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>',
        'bank'   => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l9-6 9 6"></path><path d="M4 10v9"></path><path d="M20 10v9"></path><path d="M8 10v9"></path><path d="M16 10v9"></path><line x1="2" y1="21" x2="22" y2="21"></line></svg>',
        'paypal' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 21l1.5-9h4.2c2.4 0 4.1 1.2 3.6 3.8C15.8 18.4 13.8 19 11.7 19H9.5"></path><path d="M9.5 16l1.4-9h4.2c2.4 0 4.1 1.2 3.6 3.8"></path></svg>',
        'wallet' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h15a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"></path><path d="M3 7l2-3h11l1 3"></path><circle cx="16" cy="13" r="1.4"></circle></svg>',
        'razorpay' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3 6 14h4l-2 7 8-12h-4l2-6Z"></path></svg>',
    );
    $svg = isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['wallet'];
    // Return output already escaped through wp_kses so it is safe to echo.
    return wp_kses( $svg, wptm_svg_allowed() );
}

/**
 * The premium SVG icon library (Lucide-style line icons).
 *
 * Each entry is the inner markup of a 24×24 stroked icon. Filterable so themes
 * or add-ons can override or extend the set ("icon customization").
 *
 * @return array<string,string>
 */
function wptm_icon_library() {
    $icons = array(
        'map-pin'   => '<path d="M20 10c0 4.4-8 12-8 12s-8-7.6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'mountain'  => '<path d="M3 20h18L13.7 5.3a2 2 0 0 0-3.4 0L3 20Z"/><path d="m8.5 14 2-2 2 2 2-2"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'cake'      => '<path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M4 16s.5-1 2-1 2.5 1 4 1 2.5-1 4-1 2.5 1 4 1 2-1 2-1"/><path d="M2 21h20"/><path d="M7 8v3M12 8v3M17 8v3"/>',
        'utensils'  => '<path d="M3 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2-2V2"/><path d="M5 2v20"/><path d="M19 2a3 3 0 0 0-3 3v7h3Zm0 0v20"/>',
        'bed'       => '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
        'building'  => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M16 10h.01M8 10h.01M12 14h.01M16 14h.01M8 14h.01"/>',
        'star'      => '<polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/>',
        'ruler'     => '<path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-4.6-4.6a1 1 0 0 1 0-1.4L15.3 2.7a1 1 0 0 1 1.4 0l4.6 4.6a1 1 0 0 1 0 1.4Z"/><path d="m7.5 10.5 2 2M10.5 7.5l2 2M13.5 4.5l2 2M4.5 13.5l2 2"/>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
        'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
        'heart'     => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
        'plane'     => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2a1 1 0 0 0-1.1.5l-.3.5a1 1 0 0 0 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3a1 1 0 0 0 1.3.3l.5-.2a1 1 0 0 0 .5-1.2Z"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'login'     => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18Z"/>',
        'compass'   => '<circle cx="12" cy="12" r="9"/><polygon points="16.2 7.8 14 14 7.8 16.2 10 10 16.2 7.8"/>',
        'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'tag'       => '<path d="M12.6 2.6 21 11a2 2 0 0 1 0 2.8l-7.2 7.2a2 2 0 0 1-2.8 0L2.6 12.6A2 2 0 0 1 2 11.2V4a2 2 0 0 1 2-2h7.2a2 2 0 0 1 1.4.6Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        // ── Hotel facilities ──
        'wifi'      => '<path d="M5 12.55a11 11 0 0 1 14 0"/><path d="M1.4 9a16 16 0 0 1 21.2 0"/><path d="M8.5 16.1a6 6 0 0 1 7 0"/><path d="M12 20h.01"/>',
        'waves'     => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'parking'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>',
        'coffee'    => '<path d="M10 2v3M14 2v3"/><path d="M5 8h11a1 1 0 0 1 1 1v7a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1Z"/><path d="M17 9h2a2 2 0 0 1 0 4h-2"/>',
        'dumbbell'  => '<path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>',
        'wind'      => '<path d="M12.8 19.6A2 2 0 1 0 14 16H2"/><path d="M17.5 8a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.8 4.4A2 2 0 1 1 11 8H2"/>',
        'wine'      => '<path d="M8 22h8"/><path d="M7 10h10"/><path d="M12 15v7"/><path d="M12 15a5 5 0 0 0 5-5c0-2-.5-4-1-6H8c-.5 2-1 4-1 6a5 5 0 0 0 5 5Z"/>',
        'spa'       => '<circle cx="12" cy="12" r="3"/><path d="M12 9a3 3 0 1 0-2.6-4.5A3 3 0 0 0 12 9Z"/><path d="M15 12a3 3 0 1 0 4.5-2.6A3 3 0 0 0 15 12Z"/><path d="M12 15a3 3 0 1 0 2.6 4.5A3 3 0 0 0 12 15Z"/><path d="M9 12a3 3 0 1 0-4.5 2.6A3 3 0 0 0 9 12Z"/>',
        'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'car'       => '<path d="M19 17h2v-3.3a2 2 0 0 0-.6-1.4l-1.6-1.6a2 2 0 0 0-1.4-.6H6.3a2 2 0 0 0-1.8 1.1L3 14v3h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
        'tv'        => '<rect x="2" y="7" width="20" height="13" rx="2"/><path d="m8 3 4 4 4-4"/>',
        'washing'   => '<rect x="3" y="2" width="18" height="20" rx="2"/><circle cx="12" cy="13" r="5"/><path d="M7 6h.01M11 6h.01"/>',
        'paw'       => '<circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="20" cy="16" r="2"/><path d="M9 10a5 5 0 0 1 5 5v3a3 3 0 0 1-6 0 5 5 0 0 0-5-5 3 3 0 0 1 6 0Z"/>',
        'snowflake' => '<path d="M12 2v20M2 12h20M5 5l14 14M19 5 5 19"/>',
    );

    /**
     * Filter the WPTM icon library. Add or override icons (inner SVG markup of a
     * 24×24 viewBox) to customize the icons used across the front end.
     *
     * @param array $icons name => inner SVG markup.
     */
    return apply_filters( 'wptm_icon_library', $icons );
}

/**
 * Render a premium SVG icon.
 *
 * @param string $name Icon name from wptm_icon_library().
 * @param array  $args size (int px), class (string), stroke (float), fill (bool).
 * @return string Inline SVG, or empty string if the icon is unknown.
 */
function wptm_icon( $name, $args = array() ) {
    $args = wp_parse_args( $args, array(
        'size'   => 18,
        'class'  => '',
        'stroke' => 1.9,
        'fill'   => false,
    ) );

    $library = wptm_icon_library();
    if ( ! isset( $library[ $name ] ) ) {
        return '';
    }

    $size  = (int) $args['size'];
    $class = 'wptm-icon wptm-icon--' . sanitize_html_class( $name );
    if ( $args['class'] ) {
        $class .= ' ' . $args['class'];
    }

    $svg = sprintf(
        '<svg class="%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="%3$s" stroke="currentColor" stroke-width="%4$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%5$s</svg>',
        esc_attr( $class ),
        $size,
        $args['fill'] ? 'currentColor' : 'none',
        esc_attr( (string) $args['stroke'] ),
        $library[ $name ]
    );

    /**
     * Filter a rendered WPTM icon.
     *
     * @param string $svg  SVG markup.
     * @param string $name Icon name.
     * @param array  $args Render args.
     */
    $svg = apply_filters( 'wptm_icon', $svg, $name, $args );

    // Return output already escaped through wp_kses so it is safe to echo.
    return wp_kses( $svg, wptm_svg_allowed() );
}

/**
 * Allowlist of the SVG/HTML elements the icon helpers ({@see wptm_icon()} /
 * {@see wptm_stars()}) emit. Pass it to the core wp_kses() at each icon echo site
 * — e.g. `echo wp_kses( wptm_icon( 'star' ), wptm_svg_allowed() );` — so output is
 * escaped through a function the security scanners recognise.
 *
 * @return array wp_kses allowed-HTML map.
 */
function wptm_svg_allowed() {
    $svg_atts = array(
        'class'          => true, 'style' => true, 'width' => true, 'height' => true,
        'viewbox'        => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true,
        'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true,
        'aria-hidden'    => true, 'aria-label' => true, 'focusable' => true, 'role' => true,
        'xmlns'          => true,
    );
    return array(
        'span'     => array( 'class' => true, 'style' => true, 'aria-label' => true, 'role' => true ),
        'svg'      => $svg_atts,
        'g'        => array( 'fill' => true, 'stroke' => true, 'transform' => true ),
        'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'opacity' => true ),
        'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
        'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true ),
        'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
        'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
        'polygon'  => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
    );
}

/**
 * A row of star icons for a rating (filled gold).
 *
 * @param int $count Number of stars.
 * @param int $size  Icon size.
 * @return string
 */
function wptm_stars( $count, $size = 15 ) {
    $count = max( 0, (int) $count );
    if ( ! $count ) {
        return '';
    }
    $star = wptm_icon( 'star', array( 'size' => $size, 'fill' => true, 'stroke' => 0, 'class' => 'wptm-star' ) );
    /* translators: %d: number of stars in the rating. */
    $html = '<span class="wptm-stars-row" aria-label="' . esc_attr( sprintf( _n( '%d star', '%d stars', $count, 'byteflows-travel-hotel-booking' ), $count ) ) . '">'
        . str_repeat( $star, $count ) . '</span>';
    // Safe to echo — every part is escaped and re-run through wp_kses.
    return wp_kses( $html, wptm_svg_allowed() );
}

/**
 * Standard hotel facilities mapped to icons. Filterable so sites can extend the
 * set. Used to resolve a facility name to a premium icon on the front end.
 *
 * @return array<string,string> label => icon name.
 */
function wptm_hotel_facilities() {
    return apply_filters( 'wptm_hotel_facilities', array(
        'Free WiFi'         => 'wifi',
        'Swimming Pool'     => 'waves',
        'Parking'           => 'parking',
        'Restaurant'        => 'utensils',
        'Bar'               => 'wine',
        'Gym'               => 'dumbbell',
        'Spa'               => 'spa',
        'Air Conditioning'  => 'wind',
        'Breakfast'         => 'coffee',
        'Room Service'      => 'bell',
        'Airport Shuttle'   => 'car',
        'TV'                => 'tv',
        'Laundry'           => 'washing',
        'Beach Access'      => 'sun',
        'Pet Friendly'      => 'paw',
    ) );
}

/**
 * Resolve a facility name to a premium SVG icon (exact match, then keyword).
 *
 * @param string $name Facility name.
 * @param int    $size Icon size.
 * @return string Inline SVG.
 */
function wptm_facility_icon( $name, $size = 18 ) {
    $name_l = strtolower( trim( (string) $name ) );

    foreach ( wptm_hotel_facilities() as $label => $icon ) {
        if ( strtolower( $label ) === $name_l ) {
            return wptm_icon( $icon, array( 'size' => $size ) );
        }
    }

    // Keyword fallback so custom names still get a sensible icon.
    $keywords = array(
        'wifi' => 'wifi', 'wi-fi' => 'wifi', 'internet' => 'wifi',
        'pool' => 'waves', 'swim' => 'waves',
        'park' => 'parking', 'garage' => 'parking',
        'restaurant' => 'utensils', 'dining' => 'utensils', 'breakfast' => 'coffee', 'coffee' => 'coffee',
        'bar' => 'wine', 'lounge' => 'wine', 'pub' => 'wine',
        'gym' => 'dumbbell', 'fitness' => 'dumbbell',
        'spa' => 'spa', 'sauna' => 'spa', 'massage' => 'spa', 'wellness' => 'spa', 'garden' => 'spa',
        'air' => 'wind', 'a/c' => 'wind', 'conditioning' => 'wind', 'heating' => 'wind',
        'shuttle' => 'car', 'airport' => 'car', 'transfer' => 'car', 'taxi' => 'car',
        'tv' => 'tv', 'television' => 'tv',
        'laundry' => 'washing', 'dry clean' => 'washing',
        'beach' => 'sun', 'sea' => 'sun', 'terrace' => 'sun', 'rooftop' => 'sun', 'view' => 'sun',
        'pet' => 'paw', 'dog' => 'paw',
        'room service' => 'bell', 'concierge' => 'bell', 'reception' => 'bell', '24' => 'bell',
    );
    foreach ( $keywords as $kw => $icon ) {
        if ( false !== strpos( $name_l, $kw ) ) {
            return wptm_icon( $icon, array( 'size' => $size ) );
        }
    }

    return wptm_icon( 'bell', array( 'size' => $size ) );
}

/**
 * Whether the Byteflows Travel & Hotel Booking Pro add-on is active.
 *
 * The Pro plugin defines the WPTM_PRO_VERSION constant when it boots; it can
 * also hook the 'wptm_pro_active' filter directly.
 *
 * @return bool
 */
function wptm_is_pro_active() {
    return (bool) apply_filters( 'wptm_pro_active', defined( 'WPTM_PRO_VERSION' ) );
}

/**
 * URL of the Pro add-on sales page ("Buy Now" target).
 *
 * @param string $medium Optional utm_medium value identifying the link placement.
 * @return string Filterable via 'wptm_pro_url'.
 */
function wptm_pro_url( $medium = 'admin' ) {
    $url = add_query_arg(
        array(
            'utm_source'   => 'plugin',
            'utm_medium'   => rawurlencode( $medium ),
            'utm_campaign' => 'wptm-upgrade',
        ),
        'https://byteflows.net/byteflows-travel-hotel-booking-pro/'
    );
    return apply_filters( 'wptm_pro_url', $url, $medium );
}

/**
 * Full list of country names for the enquiry "Country" field type.
 *
 * @return string[] Filterable via 'wptm_countries'.
 */
function wptm_countries() {
    $countries = array(
        'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan',
        'Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi',
        'Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo','Congo (Democratic Republic)','Costa Rica','Côte d\'Ivoire','Croatia','Cuba','Cyprus','Czechia',
        'Denmark','Djibouti','Dominica','Dominican Republic',
        'Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia',
        'Fiji','Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana',
        'Haiti','Honduras','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan',
        'Kazakhstan','Kenya','Kiribati','Kosovo','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg',
        'Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar',
        'Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway','Oman',
        'Pakistan','Palau','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Rwanda',
        'Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria',
        'Taiwan','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu',
        'Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Vanuatu','Vatican City','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe',
    );
    return apply_filters( 'wptm_countries', $countries );
}
