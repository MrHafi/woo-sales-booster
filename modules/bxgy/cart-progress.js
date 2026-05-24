jQuery(document).ready(function($) {

    var refreshTimer = null;
    var lastHtml = '';

    // Ask the server for the current message and update the DOM
    function refreshProgressMessage() {

        // Debounce: wait 400ms after the last change before firing
        clearTimeout(refreshTimer);

        refreshTimer = setTimeout(function() {

            $.post(wooSbCart.ajax_url, {
                action: 'woo_sb_get_progress',
                nonce: wooSbCart.nonce
            }, function(response) {

                if (!response.success) return;

                var newHtml = response.data.html || '';

                // If nothing changed, skip (prevents update loops)
                if (newHtml === lastHtml) return;

                lastHtml = newHtml;

                var $existing = $('.woo-sb-progress');

                if (newHtml) {
                    // Replace existing or insert at top of cart
                    if ($existing.length) {
                        $existing.replaceWith(newHtml);
                    } else {
                        $('.wp-block-woocommerce-cart, .woocommerce-cart-form, main').first().prepend(newHtml);
                    }
                } else {
                    // No message → remove existing banner
                    $existing.remove();
                }
            });
        }, 400);
    }

    // Watch the cart area for any change (qty change, item removed, etc.)
    var cartArea = document.querySelector('.wp-block-woocommerce-cart, .woocommerce-cart-form');

    if (cartArea) {
        var observer = new MutationObserver(refreshProgressMessage);
        observer.observe(cartArea, { childList: true, subtree: true });
    }
});