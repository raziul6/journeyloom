<?php
/**
 * Onboarding / setup wizard shown after activation.
 *
 * A full-screen, multi-step wizard (welcome → currency → email → pages →
 * payment → ready) that lets the site owner configure the essentials without
 * digging through Settings. Rendered standalone (WooCommerce-style) so it takes
 * over the screen with its own branded chrome.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class SetupWizard {

    /** @var string Wizard page slug. */
    const PAGE = 'wptm-setup';

    /** @var string[] Ordered step ids. */
    private $steps = array( 'welcome', 'currency', 'email', 'pages', 'payment', 'ready' );

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ), 100 );
        add_action( 'admin_init', array( $this, 'maybe_redirect' ), 1 );
        add_action( 'admin_init', array( $this, 'maybe_render' ), 5 );
        add_action( 'admin_head', array( $this, 'hide_menu_item' ) );
    }

    /**
     * Register the wizard as a real submenu page so WordPress can resolve and
     * authorise admin.php?page=wptm-setup. We intentionally do NOT call
     * remove_submenu_page(): that strips the entry from the $submenu global which
     * admin.php uses to validate the page, which would break access. The visible
     * link is hidden with CSS instead (see hide_menu_item).
     */
    public function register_page() {
        add_submenu_page( 'wptm-dashboard', __( 'Setup Wizard', 'journeyloom' ), __( 'Setup Wizard', 'journeyloom' ), 'manage_options', self::PAGE, array( $this, 'fallback_render' ) );
    }

    /**
     * Hide the wizard's menu link without unregistering the page (keeps it
     * accessible while keeping the menu clean).
     */
    public function hide_menu_item() {
        echo '<style>#adminmenu a[href$="page=' . esc_attr( self::PAGE ) . '"]{display:none!important;}</style>';
    }

    /**
     * Fallback when the admin_init interception didn't run (e.g. another plugin
     * exited early). Renders the wizard inside the normal admin page.
     */
    public function fallback_render() {
        $current = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! in_array( $current, $this->steps, true ) ) {
            $current = 'welcome';
        }
        $this->render( $current );
    }

    /**
     * One-time redirect to the wizard right after activation.
     */
    public function maybe_redirect() {
        if ( ! get_transient( 'wptm_activation_redirect' ) ) {
            return;
        }
        delete_transient( 'wptm_activation_redirect' );

        // Don't hijack bulk activations, network admin, or AJAX.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only presence check of a core activation flag.
        if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
        exit;
    }

    /**
     * Intercept the wizard page and render it full-screen, handling form saves
     * with the Post/Redirect/Get pattern.
     */
    public function maybe_render() {
        if ( ! isset( $_GET['page'] ) || self::PAGE !== $_GET['page'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'journeyloom' ) );
        }

        // Handle a step submission.
        if ( 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) && isset( $_POST['wptm_setup_nonce'] ) ) {
            check_admin_referer( 'wptm_setup_save', 'wptm_setup_nonce' );
            $step = sanitize_key( wp_unslash( $_POST['wptm_step'] ?? '' ) );
            $this->save_step( $step );
            wp_safe_redirect( $this->step_url( $this->next_step( $step ) ) );
            exit;
        }

        $current = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'welcome';
        if ( ! in_array( $current, $this->steps, true ) ) {
            $current = 'welcome';
        }

        $this->render( $current );
        exit;
    }

    /* ---------------------------------------------------------------- helpers */

    private function step_url( $step ) {
        return admin_url( 'admin.php?page=' . self::PAGE . '&step=' . $step );
    }

    private function next_step( $step ) {
        $i = array_search( $step, $this->steps, true );
        return ( false !== $i && isset( $this->steps[ $i + 1 ] ) ) ? $this->steps[ $i + 1 ] : 'ready';
    }

    private function step_titles() {
        return array(
            'welcome'  => __( 'Welcome', 'journeyloom' ),
            'currency' => __( 'Currency', 'journeyloom' ),
            'email'    => __( 'Email', 'journeyloom' ),
            'pages'    => __( 'Pages', 'journeyloom' ),
            'payment'  => __( 'Payments', 'journeyloom' ),
            'ready'    => __( 'Ready', 'journeyloom' ),
        );
    }

    /* ------------------------------------------------------------------- save */

    private function save_step( $step ) {
        // The setup-save nonce is verified in maybe_render() (check_admin_referer)
        // before this dispatcher runs, so the per-field reads below are trusted.
        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        switch ( $step ) {
            case 'currency':
                if ( isset( $_POST['wptm_currency'] ) ) {
                    update_option( 'wptm_currency', sanitize_text_field( wp_unslash( $_POST['wptm_currency'] ) ) );
                }
                if ( isset( $_POST['wptm_currency_symbol'] ) ) {
                    update_option( 'wptm_currency_symbol', sanitize_text_field( wp_unslash( $_POST['wptm_currency_symbol'] ) ) );
                }
                if ( isset( $_POST['wptm_currency_position'] ) ) {
                    $pos = sanitize_text_field( wp_unslash( $_POST['wptm_currency_position'] ) );
                    update_option( 'wptm_currency_position', in_array( $pos, array( 'before', 'after' ), true ) ? $pos : 'before' );
                }
                break;

            case 'email':
                if ( isset( $_POST['wptm_booking_email'] ) ) {
                    update_option( 'wptm_booking_email', sanitize_email( wp_unslash( $_POST['wptm_booking_email'] ) ) );
                }
                break;

            case 'pages':
                if ( ! empty( $_POST['wptm_recreate_pages'] ) && method_exists( '\JourneyLoom\Activator', 'create_pages' ) ) {
                    \JourneyLoom\Activator::create_pages();
                }
                break;

            case 'payment':
                update_option( 'wptm_manual_payment', ! empty( $_POST['wptm_manual_payment'] ) ? 1 : 0 );
                if ( isset( $_POST['wptm_bank_instructions'] ) ) {
                    update_option( 'wptm_bank_instructions', sanitize_textarea_field( wp_unslash( $_POST['wptm_bank_instructions'] ) ) );
                }
                update_option( 'wptm_stripe_enabled', ! empty( $_POST['wptm_stripe_enabled'] ) ? 1 : 0 );
                foreach ( array( 'wptm_stripe_publishable_key', 'wptm_stripe_secret_key', 'wptm_paypal_client_id', 'wptm_paypal_secret' ) as $key ) {
                    if ( isset( $_POST[ $key ] ) ) {
                        update_option( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
                    }
                }
                update_option( 'wptm_paypal_enabled', ! empty( $_POST['wptm_paypal_enabled'] ) ? 1 : 0 );
                if ( isset( $_POST['wptm_paypal_mode'] ) ) {
                    $mode = sanitize_text_field( wp_unslash( $_POST['wptm_paypal_mode'] ) );
                    update_option( 'wptm_paypal_mode', in_array( $mode, array( 'sandbox', 'live' ), true ) ? $mode : 'sandbox' );
                }
                update_option( 'wptm_setup_complete', 1 );
                break;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /* ----------------------------------------------------------------- render */

    private function render( $current ) {
        $titles = $this->step_titles();
        $idx    = array_search( $current, $this->steps, true );
        $total  = count( $this->steps );
        $pct    = (int) round( ( ( $idx ) / ( $total - 1 ) ) * 100 );

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- self-contained, escaped where dynamic.
        ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'JourneyLoom — Setup', 'journeyloom' ); ?></title>
    <?php $this->styles(); ?>
</head>
<body class="wptm-setup-body">
<div class="wptm-setup">
    <div class="wptm-setup__card">
        <aside class="wptm-setup__rail">
            <div class="wptm-setup__brand">
                <span class="wptm-setup__logo">✈</span>
                <span class="wptm-setup__brandname">JourneyLoom</span>
            </div>
            <ul class="wptm-setup__steps">
                <?php foreach ( $this->steps as $i => $sid ) :
                    $state = $i < $idx ? 'done' : ( $i === $idx ? 'active' : 'todo' ); ?>
                    <li class="wptm-step is-<?php echo esc_attr( $state ); ?>">
                        <span class="wptm-step__dot"><?php echo 'done' === $state ? '✓' : esc_html( $i + 1 ); ?></span>
                        <span class="wptm-step__label"><?php echo esc_html( $titles[ $sid ] ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="wptm-setup__progress">
                <div class="wptm-setup__progressbar" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
            </div>
            <p class="wptm-setup__pct"><?php /* translators: %d: setup completion percentage. */ echo esc_html( sprintf( __( '%d%% complete', 'journeyloom' ), $pct ) ); ?></p>
        </aside>
        <main class="wptm-setup__main">
            <?php $this->step_content( $current ); ?>
        </main>
    </div>
    <a class="wptm-setup__exit" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-dashboard' ) ); ?>">&larr; <?php esc_html_e( 'Skip & return to dashboard', 'journeyloom' ); ?></a>
</div>
</body>
</html><?php
        // phpcs:enable
    }

    private function form_open( $step ) {
        echo '<form method="post" action="' . esc_url( $this->step_url( $step ) ) . '">';
        wp_nonce_field( 'wptm_setup_save', 'wptm_setup_nonce' );
        echo '<input type="hidden" name="wptm_step" value="' . esc_attr( $step ) . '">';
    }

    private function step_content( $current ) {
        switch ( $current ) {
            case 'welcome':  $this->step_welcome();  break;
            case 'currency': $this->step_currency(); break;
            case 'email':    $this->step_email();    break;
            case 'pages':    $this->step_pages();    break;
            case 'payment':  $this->step_payment();  break;
            case 'ready':    $this->step_ready();    break;
        }
    }

    private function step_welcome() {
        ?>
        <div class="wptm-panel wptm-panel--center">
            <span class="wptm-panel__kicker"><?php esc_html_e( 'Setup Wizard', 'journeyloom' ); ?></span>
            <h1 class="wptm-panel__title"><?php esc_html_e( 'Welcome to JourneyLoom', 'journeyloom' ); ?></h1>
            <p class="wptm-panel__lead"><?php esc_html_e( 'Let’s get your travel store ready in a couple of minutes. We’ll set your currency, notification email, key pages and payment methods — you can change anything later.', 'journeyloom' ); ?></p>
            <ul class="wptm-feature-grid">
                <li><span>💱</span><?php esc_html_e( 'Currency & pricing', 'journeyloom' ); ?></li>
                <li><span>✉️</span><?php esc_html_e( 'Booking notifications', 'journeyloom' ); ?></li>
                <li><span>📄</span><?php esc_html_e( 'System pages', 'journeyloom' ); ?></li>
                <li><span>💳</span><?php esc_html_e( 'Stripe, PayPal & bank', 'journeyloom' ); ?></li>
            </ul>
            <div class="wptm-panel__actions">
                <a class="wptm-btn wptm-btn--primary" href="<?php echo esc_url( $this->step_url( 'currency' ) ); ?>"><?php esc_html_e( 'Let’s get started', 'journeyloom' ); ?> &raquo;</a>
            </div>
        </div>
        <?php
    }

    private function step_currency() {
        $cur = function_exists( 'wptm_get_currencies' ) ? wptm_get_currencies() : array( 'USD' => array( 'US Dollar', '$' ) );
        $sel = get_option( 'wptm_currency', 'USD' );
        $pos = get_option( 'wptm_currency_position', 'before' );
        $this->panel_head( __( 'Currency', 'journeyloom' ), __( 'Choose the currency customers will be charged in. The symbol fills in automatically.', 'journeyloom' ) );
        $this->form_open( 'currency' );
        ?>
        <div class="wptm-field">
            <label class="wptm-label"><?php esc_html_e( 'Store currency', 'journeyloom' ); ?></label>
            <select name="wptm_currency" id="wptm-cur" class="wptm-control">
                <?php foreach ( $cur as $code => $data ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>" data-symbol="<?php echo esc_attr( $data[1] ); ?>" <?php selected( $sel, $code ); ?>><?php echo esc_html( $data[0] . ' (' . $code . ' ' . $data[1] . ')' ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="wptm-field-row">
            <div class="wptm-field">
                <label class="wptm-label"><?php esc_html_e( 'Symbol', 'journeyloom' ); ?></label>
                <input type="text" name="wptm_currency_symbol" id="wptm-cur-sym" class="wptm-control" value="<?php echo esc_attr( get_option( 'wptm_currency_symbol', '$' ) ); ?>">
            </div>
            <div class="wptm-field">
                <label class="wptm-label"><?php esc_html_e( 'Position', 'journeyloom' ); ?></label>
                <select name="wptm_currency_position" class="wptm-control">
                    <option value="before" <?php selected( $pos, 'before' ); ?>><?php esc_html_e( 'Before — $99', 'journeyloom' ); ?></option>
                    <option value="after" <?php selected( $pos, 'after' ); ?>><?php esc_html_e( 'After — 99$', 'journeyloom' ); ?></option>
                </select>
            </div>
        </div>
        <script>
        document.getElementById('wptm-cur').addEventListener('change', function () {
            var o = this.options[this.selectedIndex];
            if (o && o.dataset.symbol) document.getElementById('wptm-cur-sym').value = o.dataset.symbol;
        });
        </script>
        <?php $this->nav( 'welcome' ); echo '</form>';
    }

    private function step_email() {
        $email = get_option( 'wptm_booking_email', get_option( 'admin_email' ) );
        $this->panel_head( __( 'Notification email', 'journeyloom' ), __( 'Where should new booking notifications be sent? Customers are always emailed their confirmation separately.', 'journeyloom' ) );
        $this->form_open( 'email' );
        ?>
        <div class="wptm-field">
            <label class="wptm-label"><?php esc_html_e( 'Admin booking email', 'journeyloom' ); ?></label>
            <input type="email" name="wptm_booking_email" class="wptm-control" value="<?php echo esc_attr( $email ); ?>" placeholder="you@example.com">
            <p class="wptm-help"><?php esc_html_e( 'Leave as your admin email if you’re not sure.', 'journeyloom' ); ?></p>
        </div>
        <?php $this->nav( 'currency' ); echo '</form>';
    }

    private function step_pages() {
        $pages = function_exists( 'wptm_get_system_pages' ) ? wptm_get_system_pages() : array();
        $this->panel_head( __( 'System pages', 'journeyloom' ), __( 'These pages power search, checkout and confirmation. They were created automatically on activation.', 'journeyloom' ) );
        $this->form_open( 'pages' );
        echo '<ul class="wptm-pagelist">';
        foreach ( $pages as $key => $pid ) {
            $ok    = $pid && get_post_status( $pid ) === 'publish';
            $title = ucwords( str_replace( '_', ' ', $key ) );
            echo '<li class="' . ( $ok ? 'is-ok' : 'is-missing' ) . '">';
            echo '<span class="wptm-pagelist__icon">' . ( $ok ? '✓' : '!' ) . '</span>';
            echo '<span class="wptm-pagelist__name">' . esc_html( $title ) . '</span>';
            if ( $ok ) {
                echo '<a class="wptm-pagelist__link" href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html__( 'Edit', 'journeyloom' ) . '</a>';
            } else {
                echo '<span class="wptm-pagelist__bad">' . esc_html__( 'Missing', 'journeyloom' ) . '</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
        echo '<label class="wptm-check"><input type="checkbox" name="wptm_recreate_pages" value="1"> ' . esc_html__( 'Re-create any missing pages when I continue', 'journeyloom' ) . '</label>';
        $this->nav( 'email' );
        echo '</form>';
    }

    private function step_payment() {
        $this->panel_head( __( 'Payment methods', 'journeyloom' ), __( 'Turn on the ways you want to get paid. You can add API keys now or later in Settings.', 'journeyloom' ) );
        $this->form_open( 'payment' );
        ?>
        <div class="wptm-gateway">
            <label class="wptm-switch"><input type="checkbox" name="wptm_manual_payment" value="1" <?php checked( get_option( 'wptm_manual_payment', 1 ) ); ?>> <b><?php esc_html_e( 'Bank Transfer (Manual)', 'journeyloom' ); ?></b></label>
            <textarea name="wptm_bank_instructions" class="wptm-control" rows="2" placeholder="<?php esc_attr_e( 'Bank transfer instructions shown on the confirmation page…', 'journeyloom' ); ?>"><?php echo esc_textarea( get_option( 'wptm_bank_instructions', '' ) ); ?></textarea>
        </div>
        <div class="wptm-gateway">
            <label class="wptm-switch"><input type="checkbox" name="wptm_stripe_enabled" value="1" <?php checked( get_option( 'wptm_stripe_enabled', 0 ) ); ?>> <b><?php esc_html_e( 'Stripe (cards, SCA-ready)', 'journeyloom' ); ?></b></label>
            <div class="wptm-field-row">
                <input type="text" name="wptm_stripe_publishable_key" class="wptm-control" value="<?php echo esc_attr( get_option( 'wptm_stripe_publishable_key', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Publishable key (pk_…)', 'journeyloom' ); ?>">
                <input type="password" name="wptm_stripe_secret_key" class="wptm-control" value="<?php echo esc_attr( get_option( 'wptm_stripe_secret_key', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Secret key (sk_…)', 'journeyloom' ); ?>">
            </div>
        </div>
        <div class="wptm-gateway">
            <label class="wptm-switch"><input type="checkbox" name="wptm_paypal_enabled" value="1" <?php checked( get_option( 'wptm_paypal_enabled', 0 ) ); ?>> <b><?php esc_html_e( 'PayPal', 'journeyloom' ); ?></b></label>
            <div class="wptm-field-row">
                <input type="text" name="wptm_paypal_client_id" class="wptm-control" value="<?php echo esc_attr( get_option( 'wptm_paypal_client_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Client ID', 'journeyloom' ); ?>">
                <input type="password" name="wptm_paypal_secret" class="wptm-control" value="<?php echo esc_attr( get_option( 'wptm_paypal_secret', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Secret', 'journeyloom' ); ?>">
                <select name="wptm_paypal_mode" class="wptm-control wptm-control--sm">
                    <option value="sandbox" <?php selected( get_option( 'wptm_paypal_mode', 'sandbox' ), 'sandbox' ); ?>><?php esc_html_e( 'Sandbox', 'journeyloom' ); ?></option>
                    <option value="live" <?php selected( get_option( 'wptm_paypal_mode', 'sandbox' ), 'live' ); ?>><?php esc_html_e( 'Live', 'journeyloom' ); ?></option>
                </select>
            </div>
        </div>
        <?php $this->nav( 'pages', __( 'Finish setup', 'journeyloom' ) ); echo '</form>';
    }

    private function step_ready() {
        update_option( 'wptm_setup_complete', 1 );
        ?>
        <div class="wptm-panel wptm-panel--center">
            <div class="wptm-done">🎉</div>
            <h1 class="wptm-panel__title"><?php esc_html_e( 'You’re all set!', 'journeyloom' ); ?></h1>
            <p class="wptm-panel__lead"><?php esc_html_e( 'JourneyLoom is configured and ready. Here’s where to go next.', 'journeyloom' ); ?></p>
            <div class="wptm-cardlinks">
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wptm_trip' ) ); ?>"><span>🧭</span><b><?php esc_html_e( 'Create your first trip', 'journeyloom' ); ?></b></a>
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wptm_hotel' ) ); ?>"><span>🏨</span><b><?php esc_html_e( 'Add a hotel', 'journeyloom' ); ?></b></a>
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-settings' ) ); ?>"><span>⚙️</span><b><?php esc_html_e( 'Fine-tune settings', 'journeyloom' ); ?></b></a>
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-dashboard' ) ); ?>"><span>📊</span><b><?php esc_html_e( 'Go to dashboard', 'journeyloom' ); ?></b></a>
            </div>
            <div class="wptm-panel__actions">
                <a class="wptm-btn wptm-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-dashboard' ) ); ?>"><?php esc_html_e( 'Go to dashboard', 'journeyloom' ); ?></a>
            </div>
        </div>
        <?php
    }

    private function panel_head( $title, $lead ) {
        echo '<div class="wptm-panel__head"><h1 class="wptm-panel__title">' . esc_html( $title ) . '</h1><p class="wptm-panel__lead">' . esc_html( $lead ) . '</p></div>';
    }

    private function nav( $back_step, $next_label = '' ) {
        $next_label = $next_label ? $next_label : __( 'Continue', 'journeyloom' );
        echo '<div class="wptm-panel__actions wptm-panel__actions--split">';
        echo '<a class="wptm-btn wptm-btn--ghost" href="' . esc_url( $this->step_url( $back_step ) ) . '">&larr; ' . esc_html__( 'Back', 'journeyloom' ) . '</a>';
        echo '<button type="submit" class="wptm-btn wptm-btn--primary">' . esc_html( $next_label ) . ' &rarr;</button>';
        echo '</div>';
    }

    /* ------------------------------------------------------------------ style */

    private function styles() {
        ?>
        <style>
        :root{
            --p:#fd4621;--p2:#ff8a3d;--ink:#1b1512;--mut:#6b6058;--bg:#faf7f5;
            --bd:#ece7e3;--card:#fff;--ok:#10b981;--bad:#ef4444;--r:16px;
            --grad:linear-gradient(150deg,#fd4621,#ff8a3d);
            --font:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        }
        *{box-sizing:border-box;}
        html,body{margin:0;padding:0;}
        .wptm-setup-body{background:var(--bg);background-image:radial-gradient(1200px 500px at 100% -10%,#fff2ec 0,transparent 60%),radial-gradient(900px 500px at -10% 110%,#fdeee8 0,transparent 55%);min-height:100vh;font-family:var(--font);color:var(--ink);-webkit-font-smoothing:antialiased;}
        .wptm-setup{max-width:1040px;margin:0 auto;padding:48px 20px 64px;display:flex;flex-direction:column;align-items:center;}
        .wptm-setup__card{width:100%;background:var(--card);border:1px solid var(--bd);border-radius:24px;box-shadow:0 30px 70px -30px rgba(70,30,10,.35);display:grid;grid-template-columns:300px 1fr;overflow:hidden;min-height:560px;}
        /* Rail */
        .wptm-setup__rail{background:var(--grad);color:#fff;padding:34px 28px;display:flex;flex-direction:column;}
        .wptm-setup__brand{display:flex;align-items:center;gap:10px;margin-bottom:34px;}
        .wptm-setup__logo{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,.18);font-size:19px;}
        .wptm-setup__brandname{font-weight:800;font-size:16px;letter-spacing:.2px;}
        .wptm-setup__steps{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;flex:1;}
        .wptm-step{display:flex;align-items:center;gap:13px;padding:11px 12px;border-radius:12px;font-weight:600;font-size:14px;color:rgba(255,255,255,.8);transition:.18s;}
        .wptm-step__dot{flex:0 0 auto;width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.35);}
        .wptm-step.is-active{background:rgba(255,255,255,.16);color:#fff;}
        .wptm-step.is-active .wptm-step__dot{background:#fff;color:var(--p);border-color:#fff;}
        .wptm-step.is-done{color:#fff;}
        .wptm-step.is-done .wptm-step__dot{background:rgba(255,255,255,.95);color:var(--p);border-color:transparent;}
        .wptm-setup__progress{height:7px;border-radius:99px;background:rgba(255,255,255,.25);overflow:hidden;margin-top:20px;}
        .wptm-setup__progressbar{height:100%;background:#fff;border-radius:99px;transition:width .4s cubic-bezier(.4,0,.2,1);}
        .wptm-setup__pct{margin:10px 0 0;font-size:12.5px;font-weight:600;color:rgba(255,255,255,.9);}
        /* Main */
        .wptm-setup__main{padding:48px 52px;display:flex;flex-direction:column;justify-content:center;}
        .wptm-panel__kicker{display:inline-block;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--p);margin-bottom:12px;}
        .wptm-panel__head{margin-bottom:26px;}
        .wptm-panel__title{font-size:30px;line-height:1.15;font-weight:800;margin:0 0 10px;letter-spacing:-.01em;}
        .wptm-panel__lead{font-size:15px;line-height:1.6;color:var(--mut);margin:0;}
        .wptm-panel--center{text-align:center;align-items:center;}
        .wptm-panel--center .wptm-panel__lead{max-width:460px;margin:0 auto;}
        .wptm-feature-grid{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:28px auto;padding:0;max-width:440px;}
        .wptm-feature-grid li{display:flex;align-items:center;gap:10px;background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:13px 15px;font-weight:600;font-size:14px;}
        .wptm-feature-grid span{font-size:18px;}
        /* Fields */
        .wptm-field{margin-bottom:18px;}
        .wptm-field-row{display:flex;gap:12px;flex-wrap:wrap;}
        .wptm-field-row .wptm-field,.wptm-field-row .wptm-control{flex:1;min-width:140px;}
        .wptm-label{display:block;font-weight:700;font-size:13.5px;margin-bottom:7px;}
        .wptm-control{width:100%;padding:12px 14px;border:1.5px solid var(--bd);border-radius:11px;font-size:14px;font-family:inherit;background:#fff;color:var(--ink);transition:.15s;}
        .wptm-control:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 3px rgba(253,70,33,.15);}
        .wptm-control--sm{max-width:130px;flex:0 0 auto;}
        .wptm-help{font-size:12.5px;color:var(--mut);margin:7px 0 0;}
        /* Gateways */
        .wptm-gateway{border:1.5px solid var(--bd);border-radius:14px;padding:16px 18px;margin-bottom:14px;}
        .wptm-switch{display:flex;align-items:center;gap:10px;font-size:14.5px;cursor:pointer;margin-bottom:12px;}
        .wptm-switch input{width:18px;height:18px;accent-color:var(--p);}
        .wptm-gateway .wptm-control{margin-top:0;}
        .wptm-gateway textarea.wptm-control{resize:vertical;}
        .wptm-check{display:flex;align-items:center;gap:9px;font-size:14px;margin-top:6px;cursor:pointer;}
        .wptm-check input{width:17px;height:17px;accent-color:var(--p);}
        /* Page list */
        .wptm-pagelist{list-style:none;margin:0 0 16px;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:9px;}
        .wptm-pagelist li{display:flex;align-items:center;gap:10px;border:1px solid var(--bd);border-radius:11px;padding:11px 13px;font-size:13.5px;font-weight:600;}
        .wptm-pagelist__icon{width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;background:var(--ok);}
        .wptm-pagelist li.is-missing .wptm-pagelist__icon{background:var(--bad);}
        .wptm-pagelist__name{flex:1;}
        .wptm-pagelist__link{color:var(--p);text-decoration:none;font-size:12.5px;}
        .wptm-pagelist__bad{color:var(--bad);font-size:12.5px;}
        /* Ready */
        .wptm-done{font-size:54px;margin-bottom:6px;}
        .wptm-cardlinks{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:26px auto;max-width:480px;}
        .wptm-cardlink{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--ink);background:var(--bg);border:1px solid var(--bd);border-radius:13px;padding:15px 16px;font-size:14px;transition:.16s;}
        .wptm-cardlink:hover{border-color:var(--p);transform:translateY(-2px);box-shadow:0 12px 24px -14px rgba(253,70,33,.45);}
        .wptm-cardlink span{font-size:22px;}
        /* Buttons + nav */
        .wptm-panel__actions{margin-top:30px;}
        .wptm-panel__actions--split{display:flex;align-items:center;justify-content:space-between;gap:12px;}
        .wptm-btn{display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;font-family:inherit;font-weight:700;font-size:15px;padding:13px 26px;border-radius:12px;text-decoration:none;transition:.16s;}
        .wptm-btn--primary{background:var(--grad);color:#fff;box-shadow:0 12px 24px -10px rgba(253,70,33,.6);}
        .wptm-btn--primary:hover{transform:translateY(-2px);box-shadow:0 16px 30px -10px rgba(253,70,33,.7);}
        .wptm-btn--ghost{background:transparent;color:var(--mut);padding:13px 14px;}
        .wptm-btn--ghost:hover{color:var(--ink);}
        .wptm-setup__exit{margin-top:22px;color:var(--mut);text-decoration:none;font-size:13.5px;font-weight:600;}
        .wptm-setup__exit:hover{color:var(--p);}
        @media(max-width:760px){
            .wptm-setup__card{grid-template-columns:1fr;}
            .wptm-setup__rail{flex-direction:row;flex-wrap:wrap;align-items:center;gap:10px;padding:22px;}
            .wptm-setup__brand{width:100%;margin-bottom:8px;}
            .wptm-setup__steps{flex-direction:row;flex-wrap:wrap;flex:1;gap:4px;}
            .wptm-step__label{display:none;}
            .wptm-setup__progress{width:100%;}
            .wptm-setup__main{padding:32px 24px;}
            .wptm-feature-grid,.wptm-cardlinks,.wptm-pagelist{grid-template-columns:1fr;}
        }
        </style>
        <?php
    }
}
