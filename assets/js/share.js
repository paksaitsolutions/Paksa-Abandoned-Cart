(function($) {
    'use strict';

    $(document).on('click', '#paksa-cr-share-cart', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('⏳ Generating...');

        $.post(paksa_cr_share.ajax_url, {
            action: 'paksa_cr_share_cart',
            nonce: paksa_cr_share.nonce
        }, function(res) {
            btn.prop('disabled', false).text('📤 Share Cart');
            if (res.success) {
                $('#paksa-cr-share-url').val(res.data.share_url);
                $('#paksa-cr-share-wa').attr('href', res.data.whatsapp_url);
                $('#paksa-cr-share-result').show();
            }
        });
    });

    $(document).on('click', '#paksa-cr-share-copy', function() {
        var url = $('#paksa-cr-share-url').val();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url);
        } else {
            var input = document.getElementById('paksa-cr-share-url');
            input.select();
            document.execCommand('copy');
        }
        $(this).text('✓ Copied!');
        var btn = $(this);
        setTimeout(function() { btn.text('📋 Copy'); }, 2000);
    });

})(jQuery);
