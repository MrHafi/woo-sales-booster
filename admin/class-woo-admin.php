<?php
/**
 * Admin Settings Page
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --------------------------- ADMIN CLASS --------------------------------------

class Woo_Admin {

    // Constructor — runs when class is loaded
    public function __construct() {
        $this->register_hooks();
    }

    // Register all admin hooks
    private function register_hooks() {
        add_action( 'admin_menu', array( $this, 'add_menu_fun' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_woo_load_tab', array( $this, 'ajax_load_tab' ) );

        add_action( 'admin_init', array( $this, 'save_whatsapp_settings' ) ); //save settings when form is submitted fpoe wjhatsapp
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) ); //notification for success save settginwhatsapp

            // COD settings save handler
        add_action( 'admin_init', array( $this, 'save_cod_settings' ) );// cod settings save handler
    }

    // Add Sales Booster menu under WooCommerce
    public function add_menu_fun() {
        add_submenu_page(
            'woocommerce', //parent slug
            'Sales Booster', //page title
            'Sales Booster',//menu title
            'manage_options',//capability
            'woo-sales-booster', //menu slug
            array( $this, 'render_settings_page' )
        );
    }

    // Load admin CSS and JS
    public function enqueue_scripts( $hook ) {

        // Only load on our settings page
        if ( $hook !== 'woocommerce_page_woo-sales-booster' ) {
            return;
        }

        wp_enqueue_style(
            'woo-admin-style',
            WOO_URL . 'admin/includes/admin.css',
            array(),
            WOO_VERSION
        );

        wp_enqueue_script(
            'woo-admin-script',
            WOO_URL . 'admin/includes/admin.js',
            array( 'jquery' ),
            WOO_VERSION,
            true
        );

        wp_localize_script(
            'woo-admin-script',
            'wooAdmin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'woo_admin_nonce' ),
            )
        );
    }

    // Handle AJAX tab load request
    public function ajax_load_tab() {

        // Security check
        check_ajax_referer( 'woo_admin_nonce', 'nonce' );

        // Get tab name safely
        $tab = isset( $_POST['tab'] ) ? sanitize_text_field( $_POST['tab'] ) : 'whatsapp';

        // Return tab HTML
        wp_send_json_success( $this->get_tab_content( $tab ) );
    }

    // -------------------- Render the main settings page --------------------
public function render_settings_page() {
    $current_tab = $this->get_current_tab();
    ?>
    <div class="wrap">
        <h1>Woo Sales Booster</h1>

        <?php $this->render_tabs( $current_tab ); ?>

        <div class="woo-tab-content">
            <?php echo $this->get_tab_content( $current_tab ); ?>
        </div>
    </div>
    <?php
}

    // Get current active tab from URL
    private function get_current_tab() {
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'whatsapp';
        return $tab;
    }

    // Render the tab All tabs
    private function render_tabs( $current_tab ) {
        $tabs = array(
            'whatsapp' => array( 'label' => 'WhatsApp Button', 'disabled' => false ),
            'cod'      => array( 'label' => 'COD Fee',         'disabled' => false ),
            'bxgy'     => array( 'label' => 'Buy X Get Y',     'disabled' => false ),
        );

        echo '<h2 class="nav-tab-wrapper">';

        foreach ( $tabs as $tab_key => $tab ) {
            $active = ( $current_tab === $tab_key ) ? 'nav-tab-active' : '';

            if ( $tab['disabled'] ) {
                echo '<span class="nav-tab disabled">' . esc_html( $tab['label'] ) . ' (Coming Soon)</span>';
            } else {
                echo '<a href="#" class="nav-tab woo-tab ' . $active . '" data-tab="' . esc_attr( $tab_key ) . '">' . esc_html( $tab['label'] ) . '</a>';
            }
        }

        echo '</h2>';
    }


    // Return tab HTML based on tab name
// Return tab HTML based on tab name
private function get_tab_content( $tab ) {

    if ( $tab === 'whatsapp' ) {
        ob_start();
        include WOO_PATH . 'templates/whatsapp-tab.php';
        return ob_get_clean();
        // cod tab
    } elseif ( $tab === 'cod' ) {
        ob_start();
        include WOO_PATH . 'templates/cod-tab.php';
        return ob_get_clean();
    } else {
        return '<p>Invalid tab.</p>';
    }
}


// 
//----------------------save WhatsApp form when user clicks Save Settings-------------------
public function save_whatsapp_settings() {

    // 1. Was our form submitted? If not, stop here.
    if ( ! isset( $_POST['woo_whatsapp_nonce'] ) ) {
        return;
    }

    // 2. Check nonce (security: make sure form came from our site)
    if ( ! wp_verify_nonce( $_POST['woo_whatsapp_nonce'], 'woo_save_whatsapp' ) ) {
        wp_die( 'Security check failed.' );
    }

    // 3. Check user permission (only admins can save)
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You are not allowed to do this.' );
    }

    // 4. Clean all inputs and build the array to save
   $clean = array(
    'enabled'         => isset( $_POST['enabled'] ) ? 'yes' : 'no',
    'whatsapp_number' => preg_replace( '/\D/', '', wp_unslash( $_POST['whatsapp_number'] ?? '' ) ),
    'button_label'    => sanitize_text_field( wp_unslash( $_POST['button_label'] ?? '' ) ),
    'message'         => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
    'button_color'    => sanitize_hex_color( wp_unslash( $_POST['button_color'] ?? '#25D366' ) ),
    'position'        => sanitize_text_field( wp_unslash( $_POST['position'] ?? 'after_add_to_cart' ) ),
);

    // 5. Save to database
    update_option( 'woo_sb_whatsapp_settings', $clean );

    // 6. Redirect with a flag, so we can show "Saved!" message
    wp_safe_redirect( admin_url( 'admin.php?page=woo-sales-booster&tab=whatsapp&saved=1' ) );
    exit;
}

//---------------------- Show success message after saving settings--------------------
public function show_admin_notices() {

    // Only show on our settings page
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'woocommerce_page_woo-sales-booster' ) {
        return; 
    }

    // Check if we just saved (URL has ?saved=1)
    if ( isset( $_GET['saved'] ) && $_GET['saved'] === '1' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }
}




//---------------------------- CODE settings save handler -------------------------------------

// Save COD form when user clicks Save Settings
public function save_cod_settings() {

    // 1. Was our COD form submitted? If not, stop here.
    if ( ! isset( $_POST['woo_cod_nonce'] ) ) {
        return;
    }

    // 2. Nonce check (security)
    if ( ! wp_verify_nonce( $_POST['woo_cod_nonce'], 'woo_save_cod' ) ) {
        wp_die( 'Security check failed.' );
    }

    // 3. Capability check (only admins)
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You are not allowed to do this.' );
    }

    // 4. Whitelist for fee type (never trust raw input for enums)
    $fee_type = isset( $_POST['fee_type'] ) ? sanitize_text_field( wp_unslash( $_POST['fee_type'] ) ) : 'flat';
    if ( ! in_array( $fee_type, array( 'flat', 'percent' ), true ) ) {
        $fee_type = 'flat';
    }

    // 5. Clean all inputs
    $clean = array(
        'enabled'    => isset( $_POST['enabled'] ) ? 'yes' : 'no',
        'fee_label'  => sanitize_text_field( wp_unslash( $_POST['fee_label'] ?? '' ) ),
        'fee_amount' => floatval( $_POST['fee_amount'] ?? 0 ),
        'fee_type'   => $fee_type,
        'threshold'  => floatval( $_POST['threshold'] ?? 0 ),
    );

    // 6. Save
    update_option( 'woo_sb_cod_settings', $clean );

    // 7. Redirect back to COD tab with success flag
    wp_safe_redirect( admin_url( 'admin.php?page=woo-sales-booster&tab=cod&saved=1' ) );
    exit;
}

}