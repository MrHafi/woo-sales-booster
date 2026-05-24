<?php
/**
 * Buy X Get Y Discount Feature
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --------------------------- BXGY MODULE CLASS --------------------------------------

class Woo_Bxgy {

    // Constructor — runs when class is loaded
    public function __construct() {
        $this->register_hooks();
    }

    // Register all WordPress hooks
private function register_hooks() {
    add_action( 'init', array( $this, 'register_rule_cpt' ) );
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    add_action( 'save_post_woo_sb_rule', array( $this, 'save_meta_box' ), 10, 2 );

    add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_discounts' ) ); //Hooks our discount logic into WooCommerce's cart calculation.
    add_filter( 'the_content', array( $this, 'show_progress_message' ) ); //succcess message on cart page when user has added some products but not enough to trigger discount yet

    
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cart_script' ) );
add_action( 'wp_ajax_woo_sb_get_progress', array( $this, 'ajax_get_progress_message' ) );
add_action( 'wp_ajax_nopriv_woo_sb_get_progress', array( $this, 'ajax_get_progress_message' ) );
}

    // Register the "Discount Rule" CPT
    public function register_rule_cpt() {

        $labels = array(
            'name'          => 'Discount Rules',
            'singular_name' => 'Discount Rule',
            'add_new'       => 'Add New Rule',
            'add_new_item'  => 'Add New Discount Rule',
            'edit_item'     => 'Edit Discount Rule',
            'new_item'      => 'New Discount Rule',
            'all_items'     => 'All Rules',
            'search_items'  => 'Search Rules',
            'not_found'     => 'No rules found.',
            'menu_name'     => 'Discount Rules',
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,           // No frontend page for rules
            'show_ui'         => true,            // Show in admin
            'show_in_menu'    => 'woocommerce',   // Under WooCommerce menu
            'capability_type' => 'post',
            'supports'        => array( 'title' ),// Only the title for now (no editor)
            'has_archive'     => false,
        );

        register_post_type( 'woo_sb_rule', $args );
    }


// Register our meta box on the rule edit screen in CPT
public function add_meta_boxes() {
    add_meta_box(
        'woo_sb_rule_details',             // Unique ID
        'Rule Details',                    // Box title shown to user
        array( $this, 'render_meta_box' ), // Function that prints the HTML
        'woo_sb_rule',                     // Only on this post type
        'normal',                          // Main column (not sidebar)
        'high'                             // Show near the top
    );
}

// Displatying Meta thingsin CPT
public function render_meta_box( $post ) {

    // Load saved values from db (empty string if missing)
    $product_id     = get_post_meta( $post->ID, '_woo_sb_trigger_product', true );
    $trigger_qty    = get_post_meta( $post->ID, '_woo_sb_trigger_qty', true );
    $discount_type  = get_post_meta( $post->ID, '_woo_sb_discount_type', true );
    $discount_value = get_post_meta( $post->ID, '_woo_sb_discount_value', true );
    $active         = get_post_meta( $post->ID, '_woo_sb_active', true );

    // Security nonce
    wp_nonce_field( 'woo_sb_save_rule', 'woo_sb_rule_nonce' );

    // Fetch all published products (limit 100 for now — easy to switch to AJAX search later)
    $products = wc_get_products( array( 'limit' => 100, 'status' => 'publish' ) );
    ?>

    <!-- UI FOR CPT -->
    <table class="form-table">

        <!-- Trigger Product, choose your product to add rule for -->
        <tr>
            <th><label for="woo_sb_trigger_product">Trigger Product</label></th>
            <td>
                        <select name="woo_sb_trigger_product" id="woo_sb_trigger_product">
                <option value="0" <?php selected( $product_id, 0 ); ?>>Any product</option>
                <?php foreach ( $products as $p ) : ?>
                    <option value="<?php echo esc_attr( $p->get_id() ); ?>" <?php selected( $product_id, $p->get_id() ); ?>>
                        <?php echo esc_html( $p->get_name() ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </td>
        </tr>

        <!-- Trigger Quantity -->
        <tr>
            <th><label for="woo_sb_trigger_qty">Trigger Quantity</label></th>
            <td>
                <input type="number" name="woo_sb_trigger_qty" id="woo_sb_trigger_qty"
                       value="<?php echo esc_attr( $trigger_qty ); ?>" min="1" step="1" class="small-text" />
                <p class="description">How many of the product must be in the cart to trigger the discount.</p>
            </td>
        </tr>

        <!-- Discount Type -->
        <tr>
            <th><label for="woo_sb_discount_type">Discount Type</label></th>
            <td>
                <select name="woo_sb_discount_type" id="woo_sb_discount_type">
                    <option value="percent" <?php selected( $discount_type, 'percent' ); ?>>Percentage</option>
                    <option value="flat"    <?php selected( $discount_type, 'flat' ); ?>>Flat amount</option>
                </select>
            </td>
        </tr>

        <!-- Discount Value -->
        <tr>
            <th><label for="woo_sb_discount_value">Discount Value</label></th>
            <td>
                <input type="number" name="woo_sb_discount_value" id="woo_sb_discount_value"
                       value="<?php echo esc_attr( $discount_value ); ?>" min="0" step="0.01" class="small-text" />
                <p class="description">Percentage (e.g., 10 = 10%) or flat amount, depending on type above.</p>
            </td>
        </tr>

        <!-- Active -->
        <tr>
            <th><label for="woo_sb_active">Active</label></th>
            <td>
                <input type="checkbox" name="woo_sb_active" id="woo_sb_active" value="yes" <?php checked( $active, 'yes' ); ?> />
                <span class="description">Inactive rules are ignored at checkout.</span>
            </td>
        </tr>

    </table>
    <?php
}

// Save the meta box fields when the rule post is saved
public function save_meta_box( $post_id, $post ) {

    // 1. Skip if our nonce field is missing or invalid
    if ( ! isset( $_POST['woo_sb_rule_nonce'] ) || ! wp_verify_nonce( $_POST['woo_sb_rule_nonce'], 'woo_sb_save_rule' ) ) {
        return;
    }

    // 2. Skip autosaves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 3. Capability check
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 4. Whitelist for discount type (enums must be validated)
    $discount_type = isset( $_POST['woo_sb_discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['woo_sb_discount_type'] ) ) : 'percent';
    if ( ! in_array( $discount_type, array( 'percent', 'flat' ), true ) ) {
        $discount_type = 'percent';
    }

    // 5. Sanitize each field and save as post meta
    update_post_meta( $post_id, '_woo_sb_trigger_product', absint( $_POST['woo_sb_trigger_product'] ?? 0 ) );
    update_post_meta( $post_id, '_woo_sb_trigger_qty',     absint( $_POST['woo_sb_trigger_qty'] ?? 0 ) );
    update_post_meta( $post_id, '_woo_sb_discount_type',   $discount_type );
    update_post_meta( $post_id, '_woo_sb_discount_value',  floatval( $_POST['woo_sb_discount_value'] ?? 0 ) );
    update_post_meta( $post_id, '_woo_sb_active',          isset( $_POST['woo_sb_active'] ) ? 'yes' : 'no' );
}




// Run on every cart calculation — apply the best matching rule
public function apply_discounts( $cart ) {

    // 1. Skip on backend (allow frontend AJAX through)
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // 2. Skip empty cart
    if ( $cart->is_empty() ) {
        return;
    }

    // 3. Load all active rules
    $rules = $this->get_active_rules();
    if ( empty( $rules ) ) {
        return;
    }

    // 4.  Store best (highest) discount found
    $best_discount = 0;
    $best_label    = '';

    foreach ( $rules as $rule ) {

        // Read meta for this rule
        $product_id    = (int) get_post_meta( $rule->ID, '_woo_sb_trigger_product', true );
        $trigger_qty   = (int) get_post_meta( $rule->ID, '_woo_sb_trigger_qty', true );
        $discount_type = get_post_meta( $rule->ID, '_woo_sb_discount_type', true );
        $value         = floatval( get_post_meta( $rule->ID, '_woo_sb_discount_value', true ) );

                // Skip incomplete rules (product_id = 0 means "any product", which IS valid)
            if ( ! $trigger_qty || $value <= 0 ) {
                continue;
            }

            // Count items in cart that match the rule
            $matched_qty      = 0;
            $matched_subtotal = 0;

            foreach ( $cart->get_cart() as $cart_item ) {

                // Match condition: either rule is "any product" (0), OR product matches exactly
                if ( $product_id === 0 || (int) $cart_item['product_id'] === $product_id ) {
                    $matched_qty      += $cart_item['quantity'];
                    $matched_subtotal += $cart_item['line_subtotal'];
                }
            }

        // Not enough in cart? Skip this rule
        if ( $matched_qty < $trigger_qty ) {
            continue;
        }

        // Calculate the discount amount
        if ( $discount_type === 'percent' ) {
            $discount = ( $matched_subtotal * $value ) / 100;
        } else {
            $discount = $value;
        }

        // Keep the biggest discount so far
        if ( $discount > $best_discount ) {
            $best_discount = $discount;
            $best_label    = $rule->post_title;
        }
    }

    // 5. Apply best discount as a NEGATIVE fee (so it shows as a discount line)
    if ( $best_discount > 0 ) {
        $cart->add_fee( $best_label, -1 * $best_discount, false );
    }
}

// Fetch only active rules from the database
private function get_active_rules() {
    return get_posts( array(
        'post_type'      => 'woo_sb_rule',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => '_woo_sb_active',
                'value' => 'yes',
            ),
        ),
    ) );
}




// Show a message when the customer is close to triggering a discount
// (e.g., "Add 1 more T-shirt to get 10% off!")
// Filter callback — prepends the progress HTML to cart page content
public function show_progress_message( $content ) {

    if ( ! is_cart() ) {
        return $content;
    }

    return $this->get_progress_message_html() . $content;
}

// Build the HTML string for the progress message (returns '' if nothing to show)
private function get_progress_message_html() {

    $cart = WC()->cart;

    if ( ! $cart || $cart->is_empty() ) {
        return '';
    }

    $rules = $this->get_active_rules();
    if ( empty( $rules ) ) {
        return '';
    }

    $best_opportunity = null;
    $best_value       = 0;

    foreach ( $rules as $rule ) {

        $product_id    = (int) get_post_meta( $rule->ID, '_woo_sb_trigger_product', true );
        $trigger_qty   = (int) get_post_meta( $rule->ID, '_woo_sb_trigger_qty', true );
        $discount_type = get_post_meta( $rule->ID, '_woo_sb_discount_type', true );
        $value         = floatval( get_post_meta( $rule->ID, '_woo_sb_discount_value', true ) );

        if ( ! $trigger_qty || $value <= 0 ) {
            continue;
        }

        $matched_qty = 0;
        foreach ( $cart->get_cart() as $cart_item ) {
            if ( $product_id === 0 || (int) $cart_item['product_id'] === $product_id ) {
                $matched_qty += $cart_item['quantity'];
            }
        }

        if ( $matched_qty >= $trigger_qty || $matched_qty === 0 ) {
            continue;
        }

        if ( $value > $best_value ) {
            $best_value       = $value;
            $best_opportunity = array(
                'product_id' => $product_id,
                'needed'     => $trigger_qty - $matched_qty,
                'value'      => $value,
                'type'       => $discount_type,
            );
        }
    }

    if ( ! $best_opportunity ) {
        return '';
    }

    $needed     = $best_opportunity['needed'];
    $value      = $best_opportunity['value'];
    $type       = $best_opportunity['type'];
    $product_id = $best_opportunity['product_id'];

    if ( $product_id === 0 ) {
        $product_text = 'more item' . ( $needed > 1 ? 's' : '' );
    } else {
        $product      = wc_get_product( $product_id );
        $product_text = $product ? 'more ' . $product->get_name() : 'more items';
    }

    if ( $type === 'percent' ) {
        $reward = $value . '% off';
    } else {
        $reward = wp_strip_all_tags( wc_price( $value ) ) . ' off';
    }

    $message = sprintf( 'Add %d %s to get %s!', $needed, esc_html( $product_text ), esc_html( $reward ) );

    return '<div class="woo-sb-progress" style="padding:14px 18px;margin:0 0 20px 0;background:#fff8e1;border-left:4px solid #ffb900;color:#3c2f00;font-weight:600;border-radius:4px;">' . $message . '</div>';
}







// AJAX endpoint — returns the current progress message HTML
public function ajax_get_progress_message() {

    // Security check
    check_ajax_referer( 'woo_sb_cart_nonce', 'nonce' );

    // Return the HTML (or empty if no opportunity)
    wp_send_json_success( array( 'html' => $this->get_progress_message_html() ) );
}
// Load the cart JS on the cart page only
public function enqueue_cart_script() {

    if ( ! is_cart() ) {
        return;
    }

    wp_enqueue_script(
        'woo-sb-cart-progress',
        WOO_URL . 'modules/bxgy/cart-progress.js',
        array( 'jquery' ),
        WOO_VERSION,
        true
    );

    // Pass PHP values (ajax URL + nonce) to JS
    wp_localize_script(
        'woo-sb-cart-progress',
        'wooSbCart',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'woo_sb_cart_nonce' ),
        )
    );
}


}