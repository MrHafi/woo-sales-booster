<?php
// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Activator Class
 */
class Woo_Activator {

    /**
     * On Activation
     */
    public static function activate() {
        self::check_woocommerce();
        self::set_default_options();
    }

    /**
     * Is WooCommerce installed?
     */
    private static function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            deactivate_plugins( plugin_basename( WOO_FILE ) );
            wp_die( 'WooCommerce is required to run this plugin.' );
        }
    }

    /**
     * Save default settings
     */
    private static function set_default_options() {
        $defaults = array(
            'enabled'         => 'no',
            'whatsapp_number' => '',
            'button_label'    => 'Order on WhatsApp',
            'button_color'    => '#25D366',
            'position'        => 'after_add_to_cart',
        );

        add_option( 'woo_sb_whatsapp_settings', $defaults );
    }
}