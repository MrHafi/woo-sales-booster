jQuery(document).ready(function($) {

    // Listen for tab clicks
    $('.woo-tab').on('click', function(e) {
        e.preventDefault();

        var $tab = $(this);
        var tabName = $tab.data('tab');

        // Update active tab style
        $('.woo-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');

        // Show loading message
        $('.woo-tab-content').html('<p>Loading...</p>');

        // Send AJAX request
        $.ajax({
            url: wooAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'woo_load_tab',
                tab: tabName,
                nonce: wooAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.woo-tab-content').html(response.data);
                } else {
                    $('.woo-tab-content').html('<p>Error loading tab.</p>');
                }
            },
            error: function() {
                $('.woo-tab-content').html('<p>Something went wrong.</p>');
            }
        });
    });
});