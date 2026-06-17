(function($) {
    'use strict';

    var shown = false;
    var config = paksa_cr_popup || {};

    function showPopup() {
        if (shown) return;
        shown = true;
        $('#paksa-cr-popup').fadeIn(300);
        // Set cookie so it doesn't show again for 24 hours
        document.cookie = 'paksa_cr_popup_shown=1; path=/; max-age=86400';
    }

    function hidePopup() {
        $('#paksa-cr-popup').fadeOut(200);
    }

    // Don't show if already shown (cookie check)
    if (document.cookie.indexOf('paksa_cr_popup_shown') !== -1) return;

    // Exit intent trigger
    if (config.trigger === 'exit' || config.trigger === 'both') {
        $(document).on('mouseleave', function(e) {
            if (e.clientY < 10) {
                showPopup();
            }
        });
    }

    // Time delay trigger
    if (config.trigger === 'timer' || config.trigger === 'both') {
        var delay = (parseInt(config.delay) || 30) * 1000;
        setTimeout(showPopup, delay);
    }

    // Mobile: show on scroll up (back gesture)
    var lastScroll = 0;
    $(window).on('scroll', function() {
        var current = $(this).scrollTop();
        if (current < lastScroll - 100 && current > 200) {
            showPopup();
        }
        lastScroll = current;
    });

    // Close popup
    $(document).on('click', '.paksa-cr-popup-close', hidePopup);
    $(document).on('click', '.paksa-cr-popup-overlay', function(e) {
        if ($(e.target).hasClass('paksa-cr-popup-overlay')) hidePopup();
    });
    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') hidePopup();
    });

    // Submit form
    $(document).on('submit', '#paksa-cr-popup-form', function(e) {
        e.preventDefault();

        var phone = $('#paksa-cr-popup-phone').val().trim();
        var name  = $('#paksa-cr-popup-name').val().trim();

        if (phone.replace(/[^0-9]/g, '').length < 7) {
            $('#paksa-cr-popup-phone').css('border-color', '#d63638').focus();
            return;
        }

        var $btn = $(this).find('.paksa-cr-popup-btn');
        $btn.prop('disabled', true).text('Saving...');

        $.post(config.ajax_url, {
            action: 'paksa_cr_save_checkout',
            nonce: config.nonce,
            phone: phone,
            name: name,
            email: ''
        }, function(res) {
            if (res.success) {
                $('#paksa-cr-popup-form').hide();
                $('.paksa-cr-popup-success').show();
                setTimeout(hidePopup, 3000);
            } else {
                $btn.prop('disabled', false).text('Save My Cart');
            }
        });
    });

})(jQuery);
