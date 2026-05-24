<?php
// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load saved settings (defaults applied if missing)
$settings = get_option( 'woo_sb_cod_settings', array() );

$enabled    = isset( $settings['enabled'] )    ? $settings['enabled']    : 'no';
$fee_label  = isset( $settings['fee_label'] )  ? $settings['fee_label']  : 'COD Fee';
$fee_amount = isset( $settings['fee_amount'] ) ? $settings['fee_amount'] : 0;
$fee_type   = isset( $settings['fee_type'] )   ? $settings['fee_type']   : 'flat';
$threshold  = isset( $settings['threshold'] )  ? $settings['threshold']  : 0;
?>

<h2>Cash on Delivery Extra Fee</h2>

<form method="post" id="woo-cod-form">

    <?php wp_nonce_field( 'woo_save_cod', 'woo_cod_nonce' ); ?>

    <table class="form-table">

        <!-- Enable Toggle -->
        <tr>
            <th scope="row"><label for="cod_enabled">Enable COD Fee</label></th>
            <td>
                <input type="checkbox" name="enabled" id="cod_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?> />
                <span class="description">Charge an extra fee when customer picks Cash on Delivery.</span>
            </td>
        </tr>

        <!-- Fee Label -->
        <tr>
            <th scope="row"><label for="fee_label">Fee Label</label></th>
            <td>
                <input type="text" name="fee_label" id="fee_label" value="<?php echo esc_attr( $fee_label ); ?>" class="regular-text" />
                <p class="description">Shown to customer in cart and checkout totals.</p>
            </td>
        </tr>

        

        <!-- Fee Type -->
        <tr>
            <th scope="row"><label for="fee_type">Fee Type</label></th>
            <td>
                <select name="fee_type" id="fee_type">
                    <option value="flat"    <?php selected( $fee_type, 'flat' ); ?>>Flat amount</option>
                    <option value="percent" <?php selected( $fee_type, 'percent' ); ?>>Percentage of cart subtotal</option>
                </select>
            </td>
        </tr>

        <!-- Fee Amount -->
        <tr>
            <th scope="row"><label for="fee_amount">Fee Amount (Amount or Percentage)</label></th>
            <td>
                <input type="number" name="fee_amount" id="fee_amount" value="<?php echo esc_attr( $fee_amount ); ?>" step="0.01" min="0" class="small-text" />
            </td>
        </tr>


        <!-- Threshold -->
        <tr>
            <th scope="row"><label for="threshold">Skip Fee Above Cart Total</label></th>
            <td>
                <input type="number" name="threshold" id="threshold" value="<?php echo esc_attr( $threshold ); ?>" step="0.01" min="0" class="small-text" />
                <p class="description">Optional. If cart subtotal is at or above this, no fee is added. Leave 0 to disable.</p>
            </td>
        </tr>

    </table>

    <p class="submit">
        <button type="submit" class="button button-primary">Save Settings</button>
    </p>

</form>