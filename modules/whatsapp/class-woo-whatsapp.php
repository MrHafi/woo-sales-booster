<?php
/**
 * WhatsApp Order Button Feature
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --------------------------- WHATSAPP MODULE CLASS --------------------------------------

class Woo_Whatsapp {

    // Saved settings from database
    private $settings = array();

    // Constructor — runs when class is loaded
    public function __construct() {
        $this->load_settings(); //loads db settings into class property for use in other methods
        $this->register_hooks();
    }

    // Load WhatsApp settings from database
    private function load_settings() {
        $this->settings = get_option( 'woo_sb_whatsapp_settings', array() );
    }

    // Register all WordPress hooks
   // Register all WordPress hooks
private function register_hooks() {

    // Don't hook anything if feature is off
    if ( ! $this->is_enabled() ) {
        return;
    }

    // Pick the hook based on user's chosen position
    $position = isset( $this->settings['position'] ) ? $this->settings['position'] : 'after_add_to_cart';

    if ( $position === 'before_add_to_cart' ) {
        $hook = 'woocommerce_before_add_to_cart_button';
    } else {
        $hook = 'woocommerce_after_add_to_cart_button';
    }

    // Tell WooCommerce: "At this spot on the product page, run render_button"
    add_action( $hook, array( $this, 'render_button' ) );

    // Load frontend CSS on shop pages
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
}

    // Check if WhatsApp feature is enabled
    private function is_enabled() {
        return isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 'yes';
    }

    // Render the WhatsApp button on product page
public function render_button() {

    // Safety check 1: feature must be enabled
    if ( ! $this->is_enabled() ) {
        return;
    }

    // Safety check 2: number must exist
    if ( empty( $this->settings['whatsapp_number'] ) ) {
        return;
    }

    // Build the link and grab button label + color
    $link  = $this->build_whatsapp_link();
    $label = ! empty( $this->settings['button_label'] ) ? $this->settings['button_label'] : 'Order on WhatsApp';
    $color = ! empty( $this->settings['button_color'] ) ? $this->settings['button_color'] : '#25D366';

    // If link is empty (no product found), stop
    if ( empty( $link ) ) {
        return;
    }
    ?>
    <a href="<?php echo esc_url( $link ); ?>"
       class="woo-sb-whatsapp-btn"
       target="_blank"
       rel="noopener"
       style="background-color: <?php echo esc_attr( $color ); ?>;">
        <?php echo esc_html( $label ); ?>
    </a>
    <?php
}

    // Build the WhatsApp link with pre-filled message 
private function build_whatsapp_link() {

    // Get the current product object (WooCommerce global)
    global $product;

    // If no product, stop
    if ( ! $product ) {
        return '';
    }

    // Get values from settings
    $number  = isset( $this->settings['whatsapp_number'] ) ? $this->settings['whatsapp_number'] : '';
    $message = isset( $this->settings['message'] )         ? $this->settings['message']         : '';

    // Replace placeholders with real product info
    $message = str_replace(
        array( '{product_name}', '{price}', '{product_url}' ),
        array( $product->get_name(), html_entity_decode( wp_strip_all_tags( wc_price( $product->get_price() ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), get_permalink( $product->get_id() ) ),        $message
    );

    // Build final wa.me link (message must be URL-safe)
    return 'https://wa.me/' . rawurlencode( $number ) . '?text=' . rawurlencode( $message );
}

 // Load button CSS on the frontend
public function enqueue_styles() {

    // Only load CSS on single product pages
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    wp_enqueue_style(
        'woo-sb-whatsapp',
        WOO_URL . 'modules/whatsapp/whatsapp.css',
        array(),
        WOO_VERSION
    );
}
}