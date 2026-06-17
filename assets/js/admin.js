(function($) {
    'use strict';

    var i18n = paksa_cr_admin.i18n || {};

    function ajax(data, callback) {
        data.action = 'paksa_cr_admin_action';
        data.nonce = paksa_cr_admin.nonce;
        $.post(paksa_cr_admin.ajax_url, data, callback).fail(function() {
            alert('Request failed. Please try again.');
        });
    }

    function copyToClipboard(text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showCopied(btn);
            });
        } else {
            // Fallback
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showCopied(btn);
        }
    }

    function showCopied(btn) {
        var $btn = $(btn);
        var original = $btn.text();
        $btn.text('✓').css('color', '#00a32a');
        setTimeout(function() {
            $btn.text(original).css('color', '');
        }, 1500);
    }

    function downloadCSV(rows) {
        if (!rows || rows.length <= 1) {
            alert(i18n.export_empty || 'No data to export.');
            return;
        }
        var BOM = '\uFEFF'; // UTF-8 BOM for Excel
        var csv = BOM + rows.map(function(r) {
            return r.map(function(c) {
                return '"' + String(c || '').replace(/"/g, '""') + '"';
            }).join(',');
        }).join('\r\n');

        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'paksa-carts-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function getSelectedIds() {
        var ids = [];
        $('.paksa-cr-check:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    // Mark as recovered
    $(document).on('click', '.paksa-cr-recover', function() {
        var btn = $(this), id = btn.data('id');
        if (!confirm(i18n.confirm_recover)) return;
        btn.prop('disabled', true);
        ajax({ action_type: 'mark_recovered', cart_id: id }, function(res) {
            if (res.success) {
                btn.closest('tr').find('.paksa-cr-status')
                    .removeClass('paksa-cr-status-abandoned')
                    .addClass('paksa-cr-status-recovered')
                    .text('Recovered');
                btn.remove();
            } else {
                btn.prop('disabled', false);
            }
        });
    });

    // Delete cart
    $(document).on('click', '.paksa-cr-delete', function() {
        var btn = $(this), id = btn.data('id');
        if (!confirm(i18n.confirm_delete)) return;
        btn.prop('disabled', true);
        ajax({ action_type: 'delete_cart', cart_id: id }, function(res) {
            if (res.success) {
                var row = btn.closest('tr');
                var detailRow = row.next('.paksa-cr-detail-row');
                row.fadeOut(300, function() { $(this).remove(); });
                detailRow.fadeOut(300, function() { $(this).remove(); });
            }
        });
    });

    // View cart detail (expand row)
    $(document).on('click', '.paksa-cr-view-detail', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var detailRow = $('#paksa-cr-detail-' + id);
        var cell = detailRow.find('.paksa-cr-detail-cell');

        if (detailRow.is(':visible')) {
            detailRow.slideUp(200);
            return;
        }

        cell.html('<p style="padding:12px;">Loading...</p>');
        detailRow.slideDown(200);

        ajax({ action_type: 'get_cart_detail', cart_id: id }, function(res) {
            if (res.success) {
                cell.html(res.data.html);
            } else {
                cell.html('<p style="color:#d63638;">Failed to load details.</p>');
            }
        });
    });

    // Copy phone
    $(document).on('click', '.paksa-cr-copy-btn', function() {
        copyToClipboard($(this).data('copy'), this);
    });

    // Copy recovery link
    $(document).on('click', '.paksa-cr-copy-link', function() {
        copyToClipboard($(this).data('link'), this);
    });

    // Export CSV
    $(document).on('click', '#paksa-cr-export, #paksa-cr-export-all', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Exporting...');
        ajax({ action_type: 'export_csv', export_status: '' }, function(res) {
            btn.prop('disabled', false).text('📥 Export CSV');
            if (res.success && res.data.csv) {
                downloadCSV(res.data.csv);
            }
        });
    });

    // Bulk apply
    $(document).on('click', '#paksa-cr-bulk-apply', function() {
        var action = $('#paksa-cr-bulk-action').val();
        var ids = getSelectedIds();

        if (!ids.length) {
            alert(i18n.no_selection);
            return;
        }

        if (action === 'delete') {
            if (!confirm(i18n.confirm_bulk)) return;
            ajax({ action_type: 'bulk_delete', ids: ids }, function(res) {
                if (res.success) location.reload();
            });
        } else if (action === 'recover') {
            if (!confirm('Mark ' + ids.length + ' carts as recovered?')) return;
            ajax({ action_type: 'bulk_recover', ids: ids }, function(res) {
                if (res.success) location.reload();
            });
        }
    });

    // Select all checkbox
    $(document).on('change', '#paksa-cr-select-all', function() {
        $('.paksa-cr-check').prop('checked', $(this).is(':checked'));
    });

    // Cleanup tool
    $(document).on('click', '#paksa-cr-cleanup', function() {
        if (!confirm(i18n.confirm_cleanup)) return;
        var btn = $(this);
        btn.prop('disabled', true);
        ajax({ action_type: 'cleanup' }, function(res) {
            btn.prop('disabled', false);
            if (res.success) {
                $('#paksa-cr-cleanup-result').text('✓ ' + res.data.message).show();
            }
        });
    });

})(jQuery);
