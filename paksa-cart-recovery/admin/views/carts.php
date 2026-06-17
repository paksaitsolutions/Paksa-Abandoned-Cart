<?php defined('ABSPATH') || exit; ?>

<div class="paksa-cr-carts">
    <div class="paksa-cr-carts-header">
        <form method="get" class="paksa-cr-filter-form">
            <input type="hidden" name="page" value="paksa-cart-recovery">
            <input type="hidden" name="tab" value="carts">

            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'paksa-cart-recovery'); ?></option>
                <?php foreach (['active', 'abandoned', 'recovered', 'expired'] as $s): ?>
                    <option value="<?php echo esc_attr($s); ?>" <?php selected($status, $s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search phone, name, email...', 'paksa-cart-recovery'); ?>">

            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php esc_attr_e('From', 'paksa-cart-recovery'); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php esc_attr_e('To', 'paksa-cart-recovery'); ?>">

            <button type="submit" class="button"><?php esc_html_e('🔍 Filter', 'paksa-cart-recovery'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=paksa-cart-recovery&tab=carts')); ?>" class="button"><?php esc_html_e('Reset', 'paksa-cart-recovery'); ?></a>
        </form>

        <div class="paksa-cr-carts-actions">
            <span class="paksa-cr-total-count"><?php printf(esc_html__('Total: %s', 'paksa-cart-recovery'), '<strong>' . number_format_i18n($total) . '</strong>'); ?></span>
            <button type="button" class="button button-secondary" id="paksa-cr-export"><?php esc_html_e('📥 Export CSV', 'paksa-cart-recovery'); ?></button>
        </div>
    </div>

    <?php if ($carts): ?>
    <table class="widefat striped paksa-cr-table">
        <thead>
            <tr>
                <th class="check-column"><input type="checkbox" id="paksa-cr-select-all"></th>
                <th><?php esc_html_e('Customer', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Phone', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Email', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Cart Total', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Items', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Status', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Abandoned', 'paksa-cart-recovery'); ?></th>
                <th><?php esc_html_e('Actions', 'paksa-cart-recovery'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($carts as $cart):
            $items = maybe_unserialize($cart->cart_data);
            $item_names = is_array($items) ? implode(', ', array_column($items, 'name')) : '';
            $items_count = is_array($items) ? array_sum(array_column($items, 'quantity')) : 0;
        ?>
            <tr data-id="<?php echo esc_attr($cart->id); ?>">
                <td><input type="checkbox" class="paksa-cr-check" value="<?php echo esc_attr($cart->id); ?>"></td>
                <td>
                    <strong><?php echo esc_html($cart->customer_name ?: '—'); ?></strong>
                    <div class="row-actions">
                        <span><a href="#" class="paksa-cr-view-detail" data-id="<?php echo esc_attr($cart->id); ?>"><?php esc_html_e('View Details', 'paksa-cart-recovery'); ?></a></span>
                    </div>
                </td>
                <td>
                    <span class="paksa-cr-phone"><?php echo esc_html($cart->phone_number ?: '—'); ?></span>
                    <?php if ($cart->phone_number): ?>
                        <button type="button" class="paksa-cr-copy-btn" data-copy="<?php echo esc_attr($cart->phone_number); ?>" title="<?php esc_attr_e('Copy Phone', 'paksa-cart-recovery'); ?>">📋</button>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($cart->email ?: '—'); ?></td>
                <td><strong><?php echo wc_price($cart->cart_total); ?></strong></td>
                <td>
                    <span class="paksa-cr-items-badge" title="<?php echo esc_attr($item_names); ?>">
                        <?php echo esc_html($items_count); ?> <?php esc_html_e('items', 'paksa-cart-recovery'); ?>
                    </span>
                </td>
                <td><span class="paksa-cr-status paksa-cr-status-<?php echo esc_attr($cart->status); ?>"><?php echo esc_html(ucfirst($cart->status)); ?></span></td>
                <td>
                    <?php if ($cart->abandoned_at): ?>
                        <span title="<?php echo esc_attr($cart->abandoned_at); ?>"><?php echo esc_html(human_time_diff(strtotime($cart->abandoned_at)) . ' ago'); ?></span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="paksa-cr-actions">
                    <?php if ($cart->status === 'abandoned'): ?>
                        <button type="button" class="button button-small button-primary paksa-cr-recover" data-id="<?php echo esc_attr($cart->id); ?>" title="<?php esc_attr_e('Mark Recovered', 'paksa-cart-recovery'); ?>">✓</button>
                    <?php endif; ?>
                    <?php if ($whatsapp_enabled && $cart->phone_number): ?>
                        <a href="<?php echo esc_url(Paksa_Recovery::get_whatsapp_url($cart)); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('WhatsApp', 'paksa-cart-recovery'); ?>">💬</a>
                    <?php endif; ?>
                    <button type="button" class="button button-small paksa-cr-copy-link" data-link="<?php echo esc_attr(Paksa_Recovery::get_recovery_url($cart->recovery_token)); ?>" title="<?php esc_attr_e('Copy Recovery Link', 'paksa-cart-recovery'); ?>">🔗</button>
                    <button type="button" class="button button-small paksa-cr-delete" data-id="<?php echo esc_attr($cart->id); ?>" title="<?php esc_attr_e('Delete', 'paksa-cart-recovery'); ?>">🗑️</button>
                </td>
            </tr>
            <tr class="paksa-cr-detail-row" id="paksa-cr-detail-<?php echo esc_attr($cart->id); ?>" style="display:none;">
                <td colspan="9" class="paksa-cr-detail-cell"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="paksa-cr-bulk-actions">
        <select id="paksa-cr-bulk-action">
            <option value=""><?php esc_html_e('Bulk Actions', 'paksa-cart-recovery'); ?></option>
            <option value="recover"><?php esc_html_e('Mark as Recovered', 'paksa-cart-recovery'); ?></option>
            <option value="delete"><?php esc_html_e('Delete', 'paksa-cart-recovery'); ?></option>
        </select>
        <button type="button" class="button" id="paksa-cr-bulk-apply"><?php esc_html_e('Apply', 'paksa-cart-recovery'); ?></button>
    </div>

    <?php if ($pages > 1): ?>
    <div class="paksa-cr-pagination">
        <?php
        echo paginate_links([
            'base'    => add_query_arg('paged', '%#%'),
            'format'  => '',
            'current' => $paged,
            'total'   => $pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);
        ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
        <div class="paksa-cr-empty">
            <p><?php esc_html_e('No carts found matching your criteria.', 'paksa-cart-recovery'); ?></p>
        </div>
    <?php endif; ?>
</div>
