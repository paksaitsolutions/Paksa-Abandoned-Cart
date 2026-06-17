<?php
defined('ABSPATH') || exit;

class Paksa_DB {

    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . PAKSA_CR_TABLE;
    }

    public static function activate(): void {
        global $wpdb;
        $table   = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(100) NOT NULL DEFAULT '',
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            customer_name VARCHAR(200) NOT NULL DEFAULT '',
            phone_number VARCHAR(30) NOT NULL DEFAULT '',
            email VARCHAR(200) NOT NULL DEFAULT '',
            cart_data LONGTEXT NOT NULL,
            cart_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            cart_items_count INT UNSIGNED NOT NULL DEFAULT 0,
            recovery_token VARCHAR(64) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            email_sent_1h TINYINT(1) NOT NULL DEFAULT 0,
            email_sent_24h TINYINT(1) NOT NULL DEFAULT 0,
            email_sent_72h TINYINT(1) NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            recovered_via VARCHAR(20) NOT NULL DEFAULT '',
            abandoned_at datetime DEFAULT NULL,
            recovered_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_session (session_id),
            KEY idx_phone (phone_number),
            KEY idx_status (status),
            KEY idx_token (recovery_token),
            KEY idx_user (user_id),
            KEY idx_abandoned (abandoned_at),
            KEY idx_status_phone (status, phone_number)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('paksa_cr_db_version', PAKSA_CR_VERSION);
        self::set_default_options();

        if (!wp_next_scheduled('paksa_cr_check_abandoned')) {
            wp_schedule_event(time(), 'every_fifteen_minutes', 'paksa_cr_check_abandoned');
        }
    }

    private static function set_default_options(): void {
        $defaults = [
            'paksa_cr_abandon_timeout'   => 30,
            'paksa_cr_retention_days'    => 90,
            'paksa_cr_token_expiry_days' => 7,
            'paksa_cr_email_enabled'     => 'no',
            'paksa_cr_email_1h'          => 'yes',
            'paksa_cr_email_24h'         => 'yes',
            'paksa_cr_email_72h'         => 'yes',
            'paksa_cr_whatsapp_enabled'  => 'yes',
            'paksa_cr_whatsapp_message'  => "Hi {customer_name}, you left items worth {cart_total} in your cart at {store_name}. Complete your order here: {recovery_link}",
            'paksa_cr_coupon_enabled'    => 'no',
            'paksa_cr_coupon_type'       => 'percent',
            'paksa_cr_coupon_amount'     => 10,
            'paksa_cr_coupon_expiry'     => 48,
            'paksa_cr_coupon_min_cart'   => 0,
            'paksa_cr_popup_enabled'     => 'no',
            'paksa_cr_popup_trigger'     => 'exit',
            'paksa_cr_popup_delay'       => 30,
            'paksa_cr_popup_heading'     => "Wait! Don't leave yet!",
            'paksa_cr_popup_text'        => "Enter your phone number and we'll save your cart for you.",
            'paksa_cr_popup_button'      => 'Save My Cart',
        ];
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Normalize Pakistani phone numbers to consistent format.
     */
    public static function normalize_phone(string $phone): string {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        // Convert +92 to 0
        if (str_starts_with($phone, '+92')) {
            $phone = '0' . substr($phone, 3);
        }
        // Convert 92 prefix (without +) to 0
        if (str_starts_with($phone, '92') && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 2);
        }
        return $phone;
    }

    /**
     * Format phone for WhatsApp (international format without +).
     */
    public static function phone_for_whatsapp(string $phone): string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '92' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '92')) {
            $phone = '92' . $phone;
        }
        return $phone;
    }

    public static function get_cart(int $id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table() . " WHERE id = %d", $id));
    }

    public static function get_cart_by_token(string $token): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table() . " WHERE recovery_token = %s AND status != 'expired'", $token));
    }

    public static function get_active_cart_by_session(string $session_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE session_id = %s AND status = 'active' ORDER BY id DESC LIMIT 1",
            $session_id
        ));
    }

    public static function get_active_cart_by_user(int $user_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
            $user_id
        ));
    }

    public static function get_active_cart_by_phone(string $phone): ?object {
        global $wpdb;
        $phone = self::normalize_phone($phone);
        if (empty($phone)) return null;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE phone_number = %s AND status = 'active' ORDER BY id DESC LIMIT 1",
            $phone
        ));
    }

    public static function insert_cart(array $data): int {
        global $wpdb;
        $data['recovery_token'] = bin2hex(random_bytes(16));
        $data['created_at']     = current_time('mysql');
        $data['updated_at']     = current_time('mysql');
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = self::normalize_phone($data['phone_number']);
        }
        $wpdb->insert(self::table(), $data);
        return (int) $wpdb->insert_id;
    }

    public static function update_cart(int $id, array $data): void {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        if (!empty($data['phone_number'])) {
            $data['phone_number'] = self::normalize_phone($data['phone_number']);
        }
        $wpdb->update(self::table(), $data, ['id' => $id]);
    }

    public static function delete_cart(int $id): void {
        global $wpdb;
        $wpdb->delete(self::table(), ['id' => $id]);
    }

    public static function get_carts(array $args = []): array {
        global $wpdb;
        $table = self::table();

        $defaults = [
            'status'    => '',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
            'orderby'   => 'id',
            'order'     => 'DESC',
            'per_page'  => 20,
            'offset'    => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE 1=1";
        $values = [];

        if ($args['status']) {
            $where .= " AND status = %s";
            $values[] = $args['status'];
        }
        if ($args['search']) {
            $where .= " AND (phone_number LIKE %s OR customer_name LIKE %s OR email LIKE %s)";
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }
        if ($args['date_from']) {
            $where .= " AND created_at >= %s";
            $values[] = $args['date_from'] . ' 00:00:00';
        }
        if ($args['date_to']) {
            $where .= " AND created_at <= %s";
            $values[] = $args['date_to'] . ' 23:59:59';
        }

        $allowed_orderby = ['id', 'cart_total', 'created_at', 'abandoned_at', 'phone_number', 'cart_items_count'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'id';
        $order   = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    public static function count_carts(array $args = []): int {
        global $wpdb;
        $table = self::table();

        $where = "WHERE 1=1";
        $values = [];

        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $values[] = $args['status'];
        }
        if (!empty($args['search'])) {
            $where .= " AND (phone_number LIKE %s OR customer_name LIKE %s OR email LIKE %s)";
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }
        if (!empty($args['date_from'])) {
            $where .= " AND created_at >= %s";
            $values[] = $args['date_from'] . ' 00:00:00';
        }
        if (!empty($args['date_to'])) {
            $where .= " AND created_at <= %s";
            $values[] = $args['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        return (int) ($values ? $wpdb->get_var($wpdb->prepare($sql, $values)) : $wpdb->get_var($sql));
    }

    public static function get_stats(): array {
        global $wpdb;
        $table = self::table();
        $today = current_time('Y-m-d');

        $total_abandoned   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'abandoned'");
        $total_recovered   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'recovered'");
        $lost_revenue      = (float) $wpdb->get_var("SELECT COALESCE(SUM(cart_total), 0) FROM {$table} WHERE status = 'abandoned'");
        $recovered_revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(cart_total), 0) FROM {$table} WHERE status = 'recovered'");
        $total_active      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");

        // Today's stats
        $today_abandoned = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'abandoned' AND DATE(abandoned_at) = %s", $today
        ));
        $today_recovered = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'recovered' AND DATE(recovered_at) = %s", $today
        ));
        $today_revenue = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cart_total), 0) FROM {$table} WHERE status = 'recovered' AND DATE(recovered_at) = %s", $today
        ));

        // This week
        $week_abandoned = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'abandoned' AND abandoned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $week_recovered = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'recovered' AND recovered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        $recovery_rate = ($total_abandoned + $total_recovered) > 0
            ? round(($total_recovered / ($total_abandoned + $total_recovered)) * 100, 1)
            : 0;

        $avg_cart_value = $total_abandoned > 0
            ? round($lost_revenue / $total_abandoned, 2)
            : 0;

        return compact(
            'total_abandoned', 'total_recovered', 'lost_revenue', 'recovered_revenue',
            'recovery_rate', 'total_active', 'today_abandoned', 'today_recovered',
            'today_revenue', 'week_abandoned', 'week_recovered', 'avg_cart_value'
        );
    }

    public static function get_top_products(int $limit = 10): array {
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results("SELECT cart_data FROM {$table} WHERE status = 'abandoned' ORDER BY id DESC LIMIT 500");

        $products = [];
        foreach ($rows as $row) {
            $items = maybe_unserialize($row->cart_data);
            if (!is_array($items)) continue;
            foreach ($items as $item) {
                $id = $item['product_id'] ?? 0;
                $name = $item['name'] ?? 'Unknown';
                if (!isset($products[$id])) {
                    $products[$id] = ['name' => $name, 'count' => 0, 'revenue' => 0];
                }
                $products[$id]['count'] += ($item['quantity'] ?? 1);
                $products[$id]['revenue'] += ($item['line_total'] ?? 0);
            }
        }

        usort($products, fn($a, $b) => $b['count'] - $a['count']);
        return array_slice($products, 0, $limit);
    }

    public static function get_report_data(string $period = 'daily', int $limit = 30): array {
        global $wpdb;
        $table = self::table();

        $format = match ($period) {
            'weekly'  => '%Y-%u',
            'monthly' => '%Y-%m',
            default   => '%Y-%m-%d',
        };

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(COALESCE(abandoned_at, created_at), %s) as period,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'recovered' THEN 1 ELSE 0 END) as recovered,
                    SUM(CASE WHEN status = 'abandoned' THEN cart_total ELSE 0 END) as lost_revenue,
                    SUM(CASE WHEN status = 'recovered' THEN cart_total ELSE 0 END) as recovered_revenue
             FROM {$table}
             WHERE status IN ('abandoned','recovered')
             GROUP BY period ORDER BY period DESC LIMIT %d",
            $format, $limit
        ));
    }

    public static function cleanup_old_carts(): int {
        global $wpdb;
        $days = (int) get_option('paksa_cr_retention_days', 90);
        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::table() . " WHERE status IN ('abandoned','expired') AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }

    public static function get_cart_count_by_status(): array {
        global $wpdb;
        $table = self::table();
        $results = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$table} GROUP BY status");
        $counts = ['active' => 0, 'abandoned' => 0, 'recovered' => 0, 'expired' => 0];
        foreach ($results as $row) {
            $counts[$row->status] = (int) $row->count;
        }
        return $counts;
    }
}
