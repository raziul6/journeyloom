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
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_menu_item' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
    }

    /**
     * Register the standalone wizard's stylesheet + script so the render step can
     * enqueue and print them (no inline &lt;style&gt;/&lt;script&gt; tags).
     */
    public function register_assets() {
        $css = WPTM_PLUGIN_DIR . 'assets/css/setup-wizard.css';
        $js  = WPTM_PLUGIN_DIR . 'assets/js/admin/setup-wizard.js';
        wp_register_style( 'wptm-setup-wizard', WPTM_PLUGIN_URL . 'assets/css/setup-wizard.css', array(), file_exists( $css ) ? filemtime( $css ) : WPTM_VERSION );
        wp_register_script( 'wptm-setup-wizard', WPTM_PLUGIN_URL . 'assets/js/admin/setup-wizard.js', array(), file_exists( $js ) ? filemtime( $js ) : WPTM_VERSION, true );
    }

    /**
     * Register the wizard as a real submenu page so WordPress can resolve and
     * authorise admin.php?page=wptm-setup. We intentionally do NOT call
     * remove_submenu_page(): that strips the entry from the $submenu global which
     * admin.php uses to validate the page, which would break access. The visible
     * link is hidden with CSS instead (see hide_menu_item).
     */
    public function register_page() {
        add_submenu_page( 'wptm-dashboard', __( 'Setup Wizard', 'byteflows-travel-hotel-booking' ), __( 'Setup Wizard', 'byteflows-travel-hotel-booking' ), 'manage_options', self::PAGE, array( $this, 'fallback_render' ) );
    }

    /**
     * Hide the wizard's menu link without unregistering the page (keeps it
     * accessible while keeping the menu clean).
     */
    public function hide_menu_item() {
        // 'common' is a core admin stylesheet present on every admin screen, so
        // attaching the rule to it hides the wizard link everywhere without an
        // inline <style> tag.
        wp_add_inline_style( 'common', '#adminmenu a[href$="page=' . esc_attr( self::PAGE ) . '"]{display:none!important;}' );
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
            wp_die( esc_html__( 'You do not have permission to access this page.', 'byteflows-travel-hotel-booking' ) );
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
            'welcome'  => __( 'Welcome', 'byteflows-travel-hotel-booking' ),
            'currency' => __( 'Currency', 'byteflows-travel-hotel-booking' ),
            'email'    => __( 'Email', 'byteflows-travel-hotel-booking' ),
            'pages'    => __( 'Pages', 'byteflows-travel-hotel-booking' ),
            'payment'  => __( 'Payments', 'byteflows-travel-hotel-booking' ),
            'ready'    => __( 'Ready', 'byteflows-travel-hotel-booking' ),
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

        // maybe_render() fires this on admin_init, before admin_enqueue_scripts,
        // so register + enqueue here to be certain the handles exist.
        $this->register_assets();
        wp_enqueue_style( 'wptm-setup-wizard' );
        wp_enqueue_script( 'wptm-setup-wizard' );

        ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'Byteflows Travel — Setup', 'byteflows-travel-hotel-booking' ); ?></title>
    <?php wp_print_styles( 'wptm-setup-wizard' ); ?>
</head>
<body class="wptm-setup-body">
<div class="wptm-setup">
    <div class="wptm-setup__card">
        <aside class="wptm-setup__rail">
            <div class="wptm-setup__brand">
                <span class="wptm-setup__logo">✈</span>
                <span class="wptm-setup__brandname">Byteflows Travel</span>
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
            <p class="wptm-setup__pct"><?php /* translators: %d: setup completion percentage. */ echo esc_html( sprintf( __( '%d%% complete', 'byteflows-travel-hotel-booking' ), $pct ) ); ?></p>
        </aside>
        <main class="wptm-setup__main">
            <?php $this->step_content( $current ); ?>
        </main>
    </div>
    <a class="wptm-setup__exit" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-dashboard' ) ); ?>">&larr; <?php esc_html_e( 'Skip & return to dashboard', 'byteflows-travel-hotel-booking' ); ?></a>
</div>
<?php wp_print_scripts( 'wptm-setup-wizard' ); ?>
</body>
</html><?php
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
            <span class="wptm-panel__kicker"><?php esc_html_e( 'Setup Wizard', 'byteflows-travel-hotel-booking' ); ?></span>
            <h1 class="wptm-panel__title"><?php esc_html_e( 'Welcome to Byteflows Travel', 'byteflows-travel-hotel-booking' ); ?></h1>
            <p class="wptm-panel__lead"><?php esc_html_e( 'Let’s get your travel store ready in a couple of minutes. We’ll set your currency, notification email, key pages and payment methods — you can change anything later.', 'byteflows-travel-hotel-booking' ); ?></p>
            <ul class="wptm-feature-grid">
                <li><span>💱</span><?php esc_html_e( 'Currency & pricing', 'byteflows-travel-hotel-booking' ); ?></li>
                <li><span>✉️</span><?php esc_html_e( 'Booking notifications', 'byteflows-travel-hotel-booking' ); ?></li>
                <li><span>📄</span><?php esc_html_e( 'System pages', 'byteflows-travel-hotel-booking' ); ?></li>
                <li><span>💳</span><?php esc_html_e( 'Bank-transfer checkout', 'byteflows-travel-hotel-booking' ); ?></li>
            </ul>
            <div class="wptm-panel__actions">
                <a class="wptm-btn wptm-btn--primary" href="<?php echo esc_url( $this->step_url( 'currency' ) ); ?>"><?php esc_html_e( 'Let’s get started', 'byteflows-travel-hotel-booking' ); ?> &raquo;</a>
            </div>
        </div>
        <?php
    }

    private function step_currency() {
        $cur = function_exists( 'wptm_get_currencies' ) ? wptm_get_currencies() : array( 'USD' => array( 'US Dollar', '$' ) );
        $sel = get_option( 'wptm_currency', 'USD' );
        $pos = get_option( 'wptm_currency_position', 'before' );
        $this->panel_head( __( 'Currency', 'byteflows-travel-hotel-booking' ), __( 'Choose the currency customers will be charged in. The symbol fills in automatically.', 'byteflows-travel-hotel-booking' ) );
        $this->form_open( 'currency' );
        ?>
        <div class="wptm-field">
            <label class="wptm-label"><?php esc_html_e( 'Store currency', 'byteflows-travel-hotel-booking' ); ?></label>
            <select name="wptm_currency" id="wptm-cur" class="wptm-control">
                <?php foreach ( $cur as $code => $data ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>" data-symbol="<?php echo esc_attr( $data[1] ); ?>" <?php selected( $sel, $code ); ?>><?php echo esc_html( $data[0] . ' (' . $code . ' ' . $data[1] . ')' ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="wptm-field-row">
            <div class="wptm-field">
                <label class="wptm-label"><?php esc_html_e( 'Symbol', 'byteflows-travel-hotel-booking' ); ?></label>
                <input type="text" name="wptm_currency_symbol" id="wptm-cur-sym" class="wptm-control" value="<?php echo esc_attr( get_option( 'wptm_currency_symbol', '$' ) ); ?>">
            </div>
            <div class="wptm-field">
                <label class="wptm-label"><?php esc_html_e( 'Position', 'byteflows-travel-hotel-booking' ); ?></label>
                <select name="wptm_currency_position" class="wptm-control">
                    <option value="before" <?php selected( $pos, 'before' ); ?>><?php esc_html_e( 'Before — $99', 'byteflows-travel-hotel-booking' ); ?></option>
                    <option value="after" <?php selected( $pos, 'after' ); ?>><?php esc_html_e( 'After — 99$', 'byteflows-travel-hotel-booking' ); ?></option>
                </select>
            </div>
        </div>
        <?php $this->nav( 'welcome' ); echo '</form>';
    }

    private function step_email() {
        $email = get_option( 'wptm_booking_email', get_option( 'admin_email' ) );
        $this->panel_head( __( 'Notification email', 'byteflows-travel-hotel-booking' ), __( 'Where should new booking notifications be sent? Customers are always emailed their confirmation separately.', 'byteflows-travel-hotel-booking' ) );
        $this->form_open( 'email' );
        ?>
        <div class="wptm-field">
            <label class="wptm-label"><?php esc_html_e( 'Admin booking email', 'byteflows-travel-hotel-booking' ); ?></label>
            <input type="email" name="wptm_booking_email" class="wptm-control" value="<?php echo esc_attr( $email ); ?>" placeholder="you@example.com">
            <p class="wptm-help"><?php esc_html_e( 'Leave as your admin email if you’re not sure.', 'byteflows-travel-hotel-booking' ); ?></p>
        </div>
        <?php $this->nav( 'currency' ); echo '</form>';
    }

    private function step_pages() {
        $pages = function_exists( 'wptm_get_system_pages' ) ? wptm_get_system_pages() : array();
        $this->panel_head( __( 'System pages', 'byteflows-travel-hotel-booking' ), __( 'These pages power search, checkout and confirmation. They were created automatically on activation.', 'byteflows-travel-hotel-booking' ) );
        $this->form_open( 'pages' );
        echo '<ul class="wptm-pagelist">';
        foreach ( $pages as $key => $pid ) {
            $ok    = $pid && get_post_status( $pid ) === 'publish';
            $title = ucwords( str_replace( '_', ' ', $key ) );
            echo '<li class="' . ( $ok ? 'is-ok' : 'is-missing' ) . '">';
            echo '<span class="wptm-pagelist__icon">' . ( $ok ? '✓' : '!' ) . '</span>';
            echo '<span class="wptm-pagelist__name">' . esc_html( $title ) . '</span>';
            if ( $ok ) {
                echo '<a class="wptm-pagelist__link" href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html__( 'Edit', 'byteflows-travel-hotel-booking' ) . '</a>';
            } else {
                echo '<span class="wptm-pagelist__bad">' . esc_html__( 'Missing', 'byteflows-travel-hotel-booking' ) . '</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
        echo '<label class="wptm-check"><input type="checkbox" name="wptm_recreate_pages" value="1"> ' . esc_html__( 'Re-create any missing pages when I continue', 'byteflows-travel-hotel-booking' ) . '</label>';
        $this->nav( 'email' );
        echo '</form>';
    }

    private function step_payment() {
        $this->panel_head( __( 'Payment methods', 'byteflows-travel-hotel-booking' ), __( 'Configure how you want to get paid. More payment options can be added later via add-ons.', 'byteflows-travel-hotel-booking' ) );
        $this->form_open( 'payment' );
        ?>
        <div class="wptm-gateway">
            <label class="wptm-switch"><input type="checkbox" name="wptm_manual_payment" value="1" <?php checked( get_option( 'wptm_manual_payment', 1 ) ); ?>> <b><?php esc_html_e( 'Bank Transfer (Manual)', 'byteflows-travel-hotel-booking' ); ?></b></label>
            <textarea name="wptm_bank_instructions" class="wptm-control" rows="2" placeholder="<?php esc_attr_e( 'Bank transfer instructions shown on the confirmation page…', 'byteflows-travel-hotel-booking' ); ?>"><?php echo esc_textarea( get_option( 'wptm_bank_instructions', '' ) ); ?></textarea>
        </div>
        <?php $this->nav( 'pages', __( 'Finish setup', 'byteflows-travel-hotel-booking' ) ); echo '</form>';
    }

    private function step_ready() {
        update_option( 'wptm_setup_complete', 1 );
        ?>
        <div class="wptm-panel wptm-panel--center">
            <div class="wptm-done">🎉</div>
            <h1 class="wptm-panel__title"><?php esc_html_e( 'You’re all set!', 'byteflows-travel-hotel-booking' ); ?></h1>
            <p class="wptm-panel__lead"><?php esc_html_e( 'Byteflows Travel is configured and ready. Here’s where to go next.', 'byteflows-travel-hotel-booking' ); ?></p>
            <div class="wptm-cardlinks">
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wptm_trip' ) ); ?>"><span>🧭</span><b><?php esc_html_e( 'Create your first trip', 'byteflows-travel-hotel-booking' ); ?></b></a>
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wptm_hotel' ) ); ?>"><span>🏨</span><b><?php esc_html_e( 'Add a hotel', 'byteflows-travel-hotel-booking' ); ?></b></a>
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-settings' ) ); ?>"><span>⚙️</span><b><?php esc_html_e( 'Fine-tune settings', 'byteflows-travel-hotel-booking' ); ?></b></a>
                <a class="wptm-cardlink" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-dashboard' ) ); ?>"><span>📊</span><b><?php esc_html_e( 'Go to dashboard', 'byteflows-travel-hotel-booking' ); ?></b></a>
            </div>
            <div class="wptm-panel__actions">
                <a class="wptm-btn wptm-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-dashboard' ) ); ?>"><?php esc_html_e( 'Go to dashboard', 'byteflows-travel-hotel-booking' ); ?></a>
            </div>
        </div>
        <?php
    }

    private function panel_head( $title, $lead ) {
        echo '<div class="wptm-panel__head"><h1 class="wptm-panel__title">' . esc_html( $title ) . '</h1><p class="wptm-panel__lead">' . esc_html( $lead ) . '</p></div>';
    }

    private function nav( $back_step, $next_label = '' ) {
        $next_label = $next_label ? $next_label : __( 'Continue', 'byteflows-travel-hotel-booking' );
        echo '<div class="wptm-panel__actions wptm-panel__actions--split">';
        echo '<a class="wptm-btn wptm-btn--ghost" href="' . esc_url( $this->step_url( $back_step ) ) . '">&larr; ' . esc_html__( 'Back', 'byteflows-travel-hotel-booking' ) . '</a>';
        echo '<button type="submit" class="wptm-btn wptm-btn--primary">' . esc_html( $next_label ) . ' &rarr;</button>';
        echo '</div>';
    }

}
