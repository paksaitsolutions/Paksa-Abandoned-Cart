<?php
defined('ABSPATH') || exit;

class Paksa_Cron {

    public function __construct() {
        add_filter('cron_schedules', [$this, 'add_schedules']);
        add_action('paksa_cr_check_abandoned', [$this, 'check_abandoned_carts']);
        add_action('paksa_cr_check_abandoned', [$this, 'send_recovery_emails']);
        add_action('paksa_cr_check_abandoned', [$this, 'expire_old_active_carts']);
        add_action('paksa_cr_check_abandoned', [Paksa_DB::class, 'cleanup_old_carts']);
    }

    public function add_schedules(array $schedules): array {
        $schedules['every_fifteen_minutes'] = [
            'interval' => 900,
            'display'  => __('Every 15 Minutes', 'paksa-cart-recovery'),
        ];
        return $schedules;
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('paksa_cr_check_abandoned');
    }

    /**
     * Mark active carts as abandoned if phone is captured and timeout exceeded.
     */
    public function check_abandoned_carts(): void {
        global $wpdb;
        $table   = Paksa_DB::table();
        $timeout = (int) get_option('paksa_cr_abandon_timeout', 30);
        $now     = current_time('mysql');

        // Get IDs before updating so we can fire webhooks
        $cart_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE status = 'active'
               AND phone_number != ''
               AND updated_at < DATE_SUB(%s, INTERVAL %d MINUTE)",
            $now, $timeout
        ));

        if (empty($cart_ids)) return;

        $ids_placeholder = implode(',', array_map('intval', $cart_ids));
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET status = 'abandoned', abandoned_at = %s
             WHERE id IN ({$ids_placeholder})",
            $now
        ));

        // Fire webhooks for each newly abandoned cart
        foreach ($cart_ids as $id) {
            Paksa_Webhooks::fire_abandoned((int) $id);
        }
    }

    /**
     * Expire active carts without phone number after 24 hours (low-value data).
     */
    public function expire_old_active_carts(): void {
        global $wpdb;
        $table = Paksa_DB::table();
        $now   = current_time('mysql');

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET status = 'expired'
             WHERE status = 'active'
               AND phone_number = ''
               AND updated_at < DATE_SUB(%s, INTERVAL 24 HOUR)",
            $now
        ));
    }

    public function send_recovery_emails(): void {
        if (get_option('paksa_cr_email_enabled', 'no') !== 'yes') return;

        global $wpdb;
        $table = Paksa_DB::table();
        $now   = current_time('mysql');

        // Allowed fields (whitelisted to prevent SQL injection)
        $schedules = [
            '1h'  => ['option' => 'paksa_cr_email_1h',  'field' => 'email_sent_1h',  'hours' => 1],
            '24h' => ['option' => 'paksa_cr_email_24h', 'field' => 'email_sent_24h', 'hours' => 24],
            '72h' => ['option' => 'paksa_cr_email_72h', 'field' => 'email_sent_72h', 'hours' => 72],
        ];

        foreach ($schedules as $type => $config) {
            if (get_option($config['option'], 'yes') !== 'yes') continue;

            // Field is hardcoded from the whitelist above — safe for direct use
            $field = $config['field'];
            $hours = $config['hours'];

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $field is from a safe whitelist
            $carts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE status = 'abandoned'
                   AND email != ''
                   AND {$field} = 0
                   AND abandoned_at <= DATE_SUB(%s, INTERVAL %d HOUR)
                 ORDER BY abandoned_at ASC
                 LIMIT 10",
                $now, $hours
            ));

            foreach ($carts as $cart) {
                $sent = Paksa_Email::send_recovery($cart, $type);
                if ($sent) {
                    Paksa_DB::update_cart($cart->id, [$field => 1]);
                }
            }
        }
    }
}
