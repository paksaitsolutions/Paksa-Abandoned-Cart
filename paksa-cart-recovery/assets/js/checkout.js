(function($) {
    'use strict';

    var saveTimer;
    var lastSaved = '';

    function getCheckoutData() {
        var phone = ($('#billing_phone').val() || $('input[name="billing_phone"]').val() || '').trim();
        var firstName = ($('#billing_first_name').val() || $('input[name="billing_first_name"]').val() || '').trim();
        var lastName = ($('#billing_last_name').val() || $('input[name="billing_last_name"]').val() || '').trim();
        var email = ($('#billing_email').val() || $('input[name="billing_email"]').val() || '').trim();

        return {
            phone: phone,
            name: (firstName + ' ' + lastName).trim(),
            email: email
        };
    }

    function saveCheckoutData() {
        var data = getCheckoutData();

        // Only save if phone is valid (min 7 digits)
        var digits = data.phone.replace(/[^0-9]/g, '');
        if (digits.length < 7) return;

        // Don't re-save same data
        var key = data.phone + '|' + data.name + '|' + data.email;
        if (key === lastSaved) return;
        lastSaved = key;

        $.post(paksa_cr.ajax_url, {
            action: 'paksa_cr_save_checkout',
            nonce: paksa_cr.nonce,
            phone: data.phone,
            name: data.name,
            email: data.email
        });
    }

    function debounceSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveCheckoutData, 1500);
    }

    // Track on checkout page when phone/name/email fields change
    if (paksa_cr.is_checkout) {
        $(document).on('change blur input', '#billing_phone, #billing_first_name, #billing_last_name, #billing_email, input[name="billing_phone"]', debounceSave);

        // WooCommerce Blocks checkout support
        var observer = new MutationObserver(function() {
            var phoneInput = document.querySelector('input[id*="phone"], input[name*="phone"]');
            if (phoneInput && !phoneInput.dataset.paksaBound) {
                phoneInput.dataset.paksaBound = '1';
                phoneInput.addEventListener('blur', debounceSave);
                phoneInput.addEventListener('change', debounceSave);
            }
        });

        // Observe checkout form for dynamic field rendering
        var checkoutForm = document.querySelector('.woocommerce-checkout, .wp-block-woocommerce-checkout');
        if (checkoutForm) {
            observer.observe(checkoutForm, { childList: true, subtree: true });
        }
    }

    // Track cart on cart page (just the cart contents, no customer info yet)
    if (!paksa_cr.is_checkout && typeof paksa_cr.ajax_url !== 'undefined') {
        // Send tracking after cart page loads
        setTimeout(function() {
            $.post(paksa_cr.ajax_url, {
                action: 'paksa_cr_track_cart',
                nonce: paksa_cr.nonce
            });
        }, 2000);
    }

})(jQuery);
