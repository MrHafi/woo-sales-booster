<?php
/**
 * COD Extra Fee Feature
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --------------------------- COD MODULE CLASS --------------------------------------

class Woo_Cod {

    // Saved settings from database
    private $settings = array();

    // Constructor — runs when class is loaded
    public function __construct() {
        $this->load_settings();
        $this->register_hooks();
    }

    // Load COD settings from database
    private function load_settings() {
        $this->settings = get_option( 'woo_sb_cod_settings', array() );
    }

    // Check if COD fee feature is enabled
    private function is_enabled() {
        return isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 'yes';
    }

    // Register all WordPress hooks
    private function register_hooks() {

        // Skip if disabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Hook into WooCommerce's cart fee calculation
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cod_fee' ) );
    }

    // Add the COD fee to the cart when COD is selected
    public function add_cod_fee( $cart ) {

        // 1. Skip on admin pages (unless this is a frontend AJAX call)
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        // 2. Skip if cart is empty
        if ( $cart->is_empty() ) {
            return;
        }

        // 3. Skip if WooCommerce session not ready
        if ( ! WC()->session ) {
            return;
        }

        // 4. Get current payment method; skip if not COD
        $chosen = WC()->session->get( 'chosen_payment_method' );
        if ( $chosen !== 'cod' ) {
            return;
        }

        // 5. Read settings into clean variables
        $amount    = isset( $this->settings['fee_amount'] ) ? floatval( $this->settings['fee_amount'] ) : 0;
        $type      = isset( $this->settings['fee_type'] )   ? $this->settings['fee_type']               : 'flat';
        $threshold = isset( $this->settings['threshold'] )  ? floatval( $this->settings['threshold'] )  : 0;
        $label     = ! empty( $this->settings['fee_label'] ) ? $this->settings['fee_label']             : 'COD Fee';

        // 6. Skip if no amount configured
        if ( $amount <= 0 ) {
            return;
        }

        // 7. Get cart subtotal (used for threshold check AND percentage calculation)
        $subtotal = $cart->get_subtotal();

        // 8. Skip if cart subtotal exceeds the threshold (only if threshold is set)
        if ( $threshold > 0 && $subtotal >= $threshold ) {
            return;
        }

        // 9. Calculate the fee amount
        if ( $type === 'percent' ) {
            $fee = ( $subtotal * $amount ) / 100;
        } else {
            $fee = $amount;
        }

        // 10. Add fee to cart (third arg false = non-taxable)
        $cart->add_fee( $label, $fee, false );
    }
}