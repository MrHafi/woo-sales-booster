<?php
/**
 * Plugin Name: Woo Sales Booster
 * Description: WhatsApp Button, COD Fee, and Buy X Get Y discount for WooCommerce.
 * Version: 1.0.0
 * Author: Hafi
 * License: GPL v2 or later
 * Text Domain: woo-sales-booster
 */

/*
* Create constants
* Create main plugin class
* Make singleton system (only one object)
* When object creates → constructor runs
* Constructor:
* loads files
* registers hooks 
*/


// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WOO_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_URL', plugin_dir_url( __FILE__ ) );
define( 'WOO_VERSION', '1.0.0' );
define( 'WOO_FILE', __FILE__ );

// --------------------------- MAIN PLUGIN CLASS --------------------------------------

/**
 * Main Plugin Class
 */
final class Woo_Sales_Booster {

    // Singleton instance= only 1 obj for the class
    private static $instance = null;

    /**
     * Get single instance */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self(); // Create the instance if it doesn't exist for current class
        }
        return self::$instance;
    }

    /**
     * Constructor — entry point of the plugin */
    private function __construct() {
        $this->load_files();
        $this->register_hooks();
    }

    /**
     * Load required files
     */
    private function load_files() {
        require_once WOO_PATH . 'woo_activator.php';
        require_once WOO_PATH . 'woo_deactivator.php';
        require_once WOO_PATH . '/admin/class-woo-admin.php';

        require_once WOO_PATH . 'modules\whatsapp\class-woo-whatsapp.php';
        require_once WOO_PATH . 'modules/cod/class-woo-cod.php';  
        require_once WOO_PATH . 'modules/bxgy/class-woo-bxgy.php';      

        
        // CREATING INSTANCES FOR DIFFERENT CLASSES
             new Woo_Admin();    // Start Admin settings page
             new Woo_Whatsapp(); // Start WhatsApp feature
             new Woo_Cod();   //  COD CLASS
             new Woo_Bxgy();



    }

    /**
     * Register activation and deactivation hooks
     */
    private function register_hooks() {
        register_activation_hook( WOO_FILE, array( 'Woo_Activator', 'activate' ) ); //Woo_Activator::activate();
        register_deactivation_hook( WOO_FILE, array( 'Woo_Deactivator', 'deactivate' ) );
    }
}

// --------------------------- ON ACTIVATION PLUGIN RUN THIS --------------------------------------

// Start the plugin
Woo_Sales_Booster::get_instance();