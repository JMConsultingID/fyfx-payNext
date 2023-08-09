(function($) {
    // Wait for the document to be ready
    $(document).ready(function() {
        // Hook into the WooCommerce notice event
        $(document.body).on('wc_add_notice', function(event, message, notice_type) {
            // Scroll to the top of the page
            $('html, body').animate({ scrollTop: 0 }, 'fast');
        });
    });
})(jQuery);