<?php defined('ABSPATH') || exit; ?>

<div class="paksa-cr-dashboard">
    <!-- Main Stats -->
    <div class="paksa-cr-stats-grid">
        <div class="paksa-cr-stat-card paksa-cr-stat-danger">
            <span class="paksa-cr-stat-icon">🛒</span>
            <div class="paksa-cr-stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats['total_abandoned'])); ?></h3>
                <p><?php esc_html_e('Abandoned Carts', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card paksa-cr-stat-success">
            <span class="paksa-cr-stat-icon">🔄</span>
            <div class="paksa-cr-stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats['total_recovered'])); ?></h3>
                <p><?php esc_html_e('Recovered Carts', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card">
            <span class="paksa-cr-stat-icon">📈</span>
            <div class="paksa-cr-stat-content">
                <h3><?php echo esc_html($stats['recovery_rate']); ?>%</h3>
                <p><?php esc_html_e('Recovery Rate', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card paksa-cr-stat-warning">
            <span class="paksa-cr-stat-icon">💰</span>
            <div class="paksa-cr-stat-content">
                <h3><?php echo wc_price($stats['lost_revenue']); ?></h3>
                <p><?php esc_html_e('Lost Revenue', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card paksa-cr-stat-success">
            <span class="paksa-cr-stat-icon">💵</span>
            <div class="paksa-cr-stat-content">
                <h3><?php echo wc_price($stats['recovered_revenue']); ?></h3>
                <p><?php esc_html_e('Recovered Revenue', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <div class="paksa-cr-stat-card">
            <span class="paksa-cr-stat-icon">🛍️</span>
            <div class="paksa-cr-stat-content">
                <h3><?php echo wc_price($stats['avg_cart_value']); ?></h3>
                <p><?php esc_html_e('Avg Cart Value', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
    </div>

    <!-- Today's Summary -->
    <div class="paksa-cr-card">
        <h2><?php esc_html_e("📅 Today's Summary", 'paksa-cart-recovery'); ?></h2>
        <div class="paksa-cr-today-stats">
            <div class="paksa-cr-today-item">
                <span class="paksa-cr-today-label"><?php esc_html_e('Abandoned Today', 'paksa-cart-recovery'); ?></span>
                <span class="paksa-cr-today-value paksa-cr-text-danger"><?php echo esc_html($stats['today_abandoned']); ?></span>
            </div>
            <div class="paksa-cr-today-item">
                <span class="paksa-cr-today-label"><?php esc_html_e('Recovered Today', 'paksa-cart-recovery'); ?></span>
                <span class="paksa-cr-today-value paksa-cr-text-success"><?php echo esc_html($stats['today_recovered']); ?></span>
            </div>
            <div class="paksa-cr-today-item">
                <span class="paksa-cr-today-label"><?php esc_html_e('Revenue Today', 'paksa-cart-recovery'); ?></span>
                <span class="paksa-cr-today-value"><?php echo wc_price($stats['today_revenue']); ?></span>
            </div>
            <div class="paksa-cr-today-item">
                <span class="paksa-cr-today-label"><?php esc_html_e('Active Carts Now', 'paksa-cart-recovery'); ?></span>
                <span class="paksa-cr-today-value"><?php echo esc_html($stats['total_active']); ?></span>
            </div>
            <div class="paksa-cr-today-item">
                <span class="paksa-cr-today-label"><?php esc_html_e('This Week Abandoned', 'paksa-cart-recovery'); ?></span>
                <span class="paksa-cr-today-value"><?php echo esc_html($stats['week_abandoned']); ?></span>
            </div>
            <div class="paksa-cr-today-item">
                <span class="paksa-cr-today-label"><?php esc_html_e('This Week Recovered', 'paksa-cart-recovery'); ?></span>
                <span class="paksa-cr-today-value"><?php echo esc_html($stats['week_recovered']); ?></span>
            </div>
        </div>
    </div>

    <div class="paksa-cr-row">
        <div class="paksa-cr-col">
            <div class="paksa-cr-card">
                <h2><?php esc_html_e('🏆 Top Abandoned Products', 'paksa-cart-recovery'); ?></h2>
                <?php if ($top_products): ?>
                <table class="widefat striped">
                    <thead><tr>
                        <th><?php esc_html_e('Product', 'paksa-cart-recovery'); ?></th>
                        <th><?php esc_html_e('Times', 'paksa-cart-recovery'); ?></th>
                        <th><?php esc_html_e('Lost Revenue', 'paksa-cart-recovery'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?php echo esc_html($product['name']); ?></td>
                            <td><strong><?php echo esc_html($product['count']); ?></strong></td>
                            <td><?php echo wc_price($product['revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="paksa-cr-empty"><?php esc_html_e('No data yet.', 'paksa-cart-recovery'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="paksa-cr-col">
            <div class="paksa-cr-card">
                <h2><?php esc_html_e('🕐 Recent Abandoned Carts', 'paksa-cart-recovery'); ?></h2>
                <?php if ($recent): ?>
                <table class="widefat striped">
                    <thead><tr>
                        <th><?php esc_html_e('Customer', 'paksa-cart-recovery'); ?></th>
                        <th><?php esc_html_e('Phone', 'paksa-cart-recovery'); ?></th>
                        <th><?php esc_html_e('Total', 'paksa-cart-recovery'); ?></th>
                        <th><?php esc_html_e('Time', 'paksa-cart-recovery'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recent as $cart): ?>
                        <tr>
                            <td><?php echo esc_html($cart->customer_name ?: '—'); ?></td>
                            <td><strong><?php echo esc_html($cart->phone_number); ?></strong></td>
                            <td><?php echo wc_price($cart->cart_total); ?></td>
                            <td><?php echo $cart->abandoned_at ? esc_html(human_time_diff(strtotime($cart->abandoned_at)) . ' ago') : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="text-align:right;margin-top:8px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=paksa-cart-recovery&tab=carts&status=abandoned')); ?>"><?php esc_html_e('View All →', 'paksa-cart-recovery'); ?></a>
                </p>
                <?php else: ?>
                    <p class="paksa-cr-empty"><?php esc_html_e('No abandoned carts yet. They will appear here once detected.', 'paksa-cart-recovery'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
