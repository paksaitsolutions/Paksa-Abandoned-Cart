<?php defined('ABSPATH') || exit; ?>

<div class="paksa-cr-reports">
    <!-- Quick Stats -->
    <div class="paksa-cr-stats-grid paksa-cr-stats-small">
        <div class="paksa-cr-stat-card">
            <div class="paksa-cr-stat-content">
                <h3><?php echo wc_price($stats['lost_revenue']); ?></h3>
                <p><?php esc_html_e('Total Lost', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card paksa-cr-stat-success">
            <div class="paksa-cr-stat-content">
                <h3><?php echo wc_price($stats['recovered_revenue']); ?></h3>
                <p><?php esc_html_e('Total Recovered', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card">
            <div class="paksa-cr-stat-content">
                <h3><?php echo esc_html($stats['recovery_rate']); ?>%</h3>
                <p><?php esc_html_e('Overall Rate', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
    </div>

    <div class="paksa-cr-card">
        <div class="paksa-cr-report-header">
            <h2><?php esc_html_e('📊 Recovery Report', 'paksa-cart-recovery'); ?></h2>
            <form method="get" class="paksa-cr-filter-form">
                <input type="hidden" name="page" value="paksa-cart-recovery">
                <input type="hidden" name="tab" value="reports">
                <select name="period">
                    <option value="daily" <?php selected($period, 'daily'); ?>><?php esc_html_e('📅 Daily', 'paksa-cart-recovery'); ?></option>
                    <option value="weekly" <?php selected($period, 'weekly'); ?>><?php esc_html_e('📆 Weekly', 'paksa-cart-recovery'); ?></option>
                    <option value="monthly" <?php selected($period, 'monthly'); ?>><?php esc_html_e('🗓️ Monthly', 'paksa-cart-recovery'); ?></option>
                </select>
                <button type="submit" class="button"><?php esc_html_e('View', 'paksa-cart-recovery'); ?></button>
            </form>
        </div>

        <?php if ($data): ?>
        <table class="widefat striped paksa-cr-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Period', 'paksa-cart-recovery'); ?></th>
                    <th><?php esc_html_e('Total Carts', 'paksa-cart-recovery'); ?></th>
                    <th><?php esc_html_e('Recovered', 'paksa-cart-recovery'); ?></th>
                    <th><?php esc_html_e('Recovery Rate', 'paksa-cart-recovery'); ?></th>
                    <th><?php esc_html_e('Lost Revenue', 'paksa-cart-recovery'); ?></th>
                    <th><?php esc_html_e('Recovered Revenue', 'paksa-cart-recovery'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $row):
                $rate = $row->total > 0 ? round(($row->recovered / $row->total) * 100, 1) : 0;
                $rate_class = $rate >= 30 ? 'paksa-cr-text-success' : ($rate >= 15 ? 'paksa-cr-text-warning' : 'paksa-cr-text-danger');
            ?>
                <tr>
                    <td><strong><?php echo esc_html($row->period); ?></strong></td>
                    <td><?php echo esc_html(number_format_i18n($row->total)); ?></td>
                    <td><?php echo esc_html(number_format_i18n($row->recovered)); ?></td>
                    <td><span class="<?php echo esc_attr($rate_class); ?>"><?php echo esc_html($rate); ?>%</span></td>
                    <td><?php echo wc_price($row->lost_revenue); ?></td>
                    <td><strong><?php echo wc_price($row->recovered_revenue); ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="paksa-cr-empty"><?php esc_html_e('No report data available yet. Data will appear once carts are abandoned and recovered.', 'paksa-cart-recovery'); ?></p>
        <?php endif; ?>
    </div>
</div>
