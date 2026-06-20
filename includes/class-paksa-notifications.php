<?php
defined('ABSPATH') || exit;

/**
 * WordPress Dashboard Widget + Admin Email Alerts for high-value abandoned carts.
 */
class Paksa_Notifications {

    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        add_action('paksa_cr_check_abandoned', [$this, 'notify_high_value_carts']);
    }

    /**
     * Add a widget to the WordPress admin dashboard.
     */
    public function add_dashboard_widget(): void {
        if (!current_user_can('manage_woocommerce')) return;

        wp_add_dashboard_widget(
            'paksa_cr_dashboard_widget',
            __('🛒 Paksa Cart Recovery', 'paksa-cart-recovery'),
            [$this, 'render_widget']
        );
    }

    public function render_widget(): void {
        $stats = Paksa_DB::get_stats();
        ?>
        <div class="paksa-cr-widget">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div style="background:#fcf0f1;padding:12px;border-radius:6px;text-align:center;">
                    <div style="font-size:24px;font-weight:700;color:#d63638;"><?php echo esc_html($stats['today_abandoned']); ?></div>
                    <div style="font-size:11px;color:#666;"><?php esc_html_e('Abandoned Today', 'paksa-cart-recovery'); ?></div>
                </div>
                <div style="background:#ecf7ed;padding:12px;border-radius:6px;text-align:center;">
                    <div style="font-size:24px;font-weight:700;color:#00a32a;"><?php echo esc_html($stats['today_recovered']); ?></div>
                    <div style="font-size:11px;color:#666;"><?php esc_html_e('Recovered Today', 'paksa-cart-recovery'); ?></div>
                </div>
            </div>
            <table style="width:100%;font-size:13px;">
                <tr><td><?php esc_html_e('Total Abandoned', 'paksa-cart-recovery'); ?></td><td style="text-align:right;font-weight:600;"><?php echo esc_html(number_format_i18n($stats['total_abandoned'])); ?></td></tr>
                <tr><td><?php esc_html_e('Recovery Rate', 'paksa-cart-recovery'); ?></td><td style="text-align:right;font-weight:600;"><?php echo esc_html($stats['recovery_rate']); ?>%</td></tr>
                <tr><td><?php esc_html_e('Lost Revenue', 'paksa-cart-recovery'); ?></td><td style="text-align:right;font-weight:600;color:#d63638;"><?php echo wc_price($stats['lost_revenue']); ?></td></tr>
                <tr><td><?php esc_html_e('Recovered Revenue', 'paksa-cart-recovery'); ?></td><td style="text-align:right;font-weight:600;color:#00a32a;"><?php echo wc_price($stats['recovered_revenue']); ?></td></tr>
                <tr><td><?php esc_html_e('Active Carts Now', 'paksa-cart-recovery'); ?></td><td style="text-align:right;font-weight:600;"><?php echo esc_html($stats['total_active']); ?></td></tr>
            </table>
            <p style="margin:12px 0 0;text-align:right;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=paksa-cart-recovery')); ?>" class="button button-small"><?php esc_html_e('View Dashboard →', 'paksa-cart-recovery'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Email admin when a high-value cart is abandoned.
     */
    public function notify_high_value_carts(): void {
        if (get_option('paksa_cr_admin_notify', 'no') !== 'yes') return;

        $threshold = (float) get_option('paksa_cr_admin_notify_threshold', 5000);
        if ($threshold <= 0) return;

        global $wpdb;
        $table = Paksa_DB::table();

        // Find carts just abandoned (within last 15 min) that exceed threshold and haven't been notified
        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status = 'abandoned'
               AND cart_total >= %f
               AND abandoned_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)
             ORDER BY cart_total DESC
             LIMIT 5",
            $threshold
        ));

        if (empty($carts)) return;

        $notified = get_option('paksa_cr_notified_ids', []);
        if (!is_array($notified)) $notified = [];

        $to_notify = [];
        foreach ($carts as $cart) {
            if (!in_array($cart->id, $notified)) {
                $to_notify[] = $cart;
                $notified[] = $cart->id;
            }
        }

        if (empty($to_notify)) return;

        // Keep only last 100 notified IDs
        $notified = array_slice($notified, -100);
        update_option('paksa_cr_notified_ids', $notified);

        // Send email
        $admin_email = get_option('paksa_cr_admin_notify_email', get_option('admin_email'));
        $store_name  = get_bloginfo('name');

        $subject = sprintf(__('[%s] High-Value Cart Abandoned - %s', 'paksa-cart-recovery'), $store_name, strip_tags(wc_price($to_notify[0]->cart_total)));

        $body = '<h2>' . __('🚨 High-Value Cart Abandoned', 'paksa-cart-recovery') . '</h2>';
        $body .= '<table style="width:100%;border-collapse:collapse;">';
        $body .= '<tr style="background:#f0f0f1;"><th style="padding:8px;text-align:left;">Customer</th><th style="padding:8px;">Phone</th><th style="padding:8px;">Total</th><th style="padding:8px;">Location</th></tr>';

        foreach ($to_notify as $cart) {
            $body .= '<tr style="border-bottom:1px solid #ddd;">';
            $body .= '<td style="padding:8px;">' . esc_html($cart->customer_name ?: '—') . '</td>';
            $body .= '<td style="padding:8px;font-weight:600;">' . esc_html($cart->phone_number) . '</td>';
            $body .= '<td style="padding:8px;font-weight:600;color:#d63638;">' . wc_price($cart->cart_total) . '</td>';
            $body .= '<td style="padding:8px;">' . esc_html($cart->location ?: '—') . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
        $body .= '<p style="margin-top:16px;"><a href="' . admin_url('admin.php?page=paksa-cart-recovery&tab=carts&status=abandoned') . '">' . __('View in Admin →', 'paksa-cart-recovery') . '</a></p>';

        wp_mail($admin_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }
}
