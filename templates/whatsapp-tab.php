<?php
// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load saved settings (defaults applied if missing)
$settings = get_option( 'woo_sb_whatsapp_settings', array() );

$enabled         = isset( $settings['enabled'] )         ? $settings['enabled']         : 'no';
$whatsapp_number = isset( $settings['whatsapp_number'] ) ? $settings['whatsapp_number'] : '';
$button_label    = isset( $settings['button_label'] )    ? $settings['button_label']    : 'Order on WhatsApp';
$message         = isset( $settings['message'] )         ? $settings['message']         : "Hi! I'm interested in {product_name} ({price}). Link: {product_url}";
$button_color    = isset( $settings['button_color'] )    ? $settings['button_color']    : '#25D366';
$position        = isset( $settings['position'] )        ? $settings['position']        : 'after_add_to_cart';
?>

<h2>WhatsApp Order Button Settings</h2>

<form method="post" id="woo-whatsapp-form">

    <?php wp_nonce_field( 'woo_save_whatsapp', 'woo_whatsapp_nonce' ); ?>

    <table class="form-table">

        <!-- Enable Toggle -->
        <tr>
            <th scope="row"><label for="enabled">Enable WhatsApp Button</label></th>
            <td>
                <input type="checkbox" name="enabled" id="enabled" value="yes" <?php checked( $enabled, 'yes' ); ?> />
                <span class="description">Show the WhatsApp button on product pages.</span>
            </td>
        </tr>

        <!-- WhatsApp Number -->
        <tr>
            <th scope="row"><label for="whatsapp_number">WhatsApp Number</label></th>
            <td>
                <input type="text" name="whatsapp_number" id="whatsapp_number" value="<?php echo esc_attr( $whatsapp_number ); ?>" class="regular-text" placeholder="e.g. 919876543210" />
                <p class="description">Enter with country code, no + sign or spaces.</p>
            </td>
        </tr>

        <!-- Button Label -->
        <tr>
            <th scope="row"><label for="button_label">Button Label</label></th>
            <td>
                <input type="text" name="button_label" id="button_label" value="<?php echo esc_attr( $button_label ); ?>" class="regular-text" />
            </td>
        </tr>

        <!-- Message Template -->
        <tr>
            <th scope="row"><label for="message">Message Template</label></th>
            <td>
                <textarea name="message" id="message" rows="4" class="large-text"><?php echo esc_textarea( $message ); ?></textarea>
                <p class="description">
                    Available placeholders:
                    <code>{product_name}</code>,
                    <code>{price}</code>,
                    <code>{product_url}</code>
                </p>
            </td>
        </tr>

        <!-- Button Color -->
        <tr>
            <th scope="row"><label for="button_color">Button Color</label></th>
            <td>
                <input type="color" name="button_color" id="button_color" value="<?php echo esc_attr( $button_color ); ?>" />
            </td>
        </tr>

        <!-- Position -->
        <tr>
            <th scope="row"><label for="position">Button Position</label></th>
            <td>
                <select name="position" id="position">
                    <option value="before_add_to_cart" <?php selected( $position, 'before_add_to_cart' ); ?>>Before Add to Cart</option>
                    <option value="after_add_to_cart"  <?php selected( $position, 'after_add_to_cart' ); ?>>After Add to Cart</option>
                </select>
            </td>
        </tr>

    </table>

    <p class="submit">
        <button type="submit" class="button button-primary">Save Settings</button>
    </p>

</form>