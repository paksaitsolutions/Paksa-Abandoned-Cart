<?php
defined('ABSPATH') || exit;

class Paksa_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_paksa_cr_admin_action', [$this, 'handle_ajax']);
    }

    public function add_menu(): void {
        add_submenu_page(
            'woocommerce',
            __('Paksa Cart Recovery', 'paksa-cart-recovery'),
            __('Paksa Cart Recovery', 'paksa-cart-recovery'),
            'manage_woocommerce',
            'paksa-cart-recovery',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void {
        if (strpos($hook, 'paksa-cart-recovery') === false) return;

        wp_enqueue_style('paksa-cr-admin', PAKSA_CR_URL . 'assets/css/admin.css', [], PAKSA_CR_VERSION);
        wp_enqueue_script('paksa-cr-admin', PAKSA_CR_URL . 'assets/js/admin.js', ['jquery'], PAKSA_CR_VERSION, true);
        wp_localize_script('paksa-cr-admin', 'paksa_cr_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('paksa_cr_admin_nonce'),
            'i18n'     => [
                'confirm_recover' => __('Mark this cart as recovered?', 'paksa-cart-recovery'),
                'confirm_delete'  => __('Delete this cart permanently?', 'paksa-cart-recovery'),
                'confirm_bulk'    => __('Delete selected carts?', 'paksa-cart-recovery'),
                'confirm_cleanup' => __('Run database cleanup? This cannot be undone.', 'paksa-cart-recovery'),
                'no_selection'    => __('Please select at least one cart.', 'paksa-cart-recovery'),
                'copied'          => __('Copied!', 'paksa-cart-recovery'),
                'export_empty'    => __('No data to export.', 'paksa-cart-recovery'),
            ],
        ]);
    }

    public function render_page(): void {
        $tab = sanitize_text_field($_GET['tab'] ?? 'dashboard');

        echo '<div class="wrap paksa-cr-wrap">';
        echo '<h1>' . esc_html__('Paksa Cart Recovery', 'paksa-cart-recovery') . ' <span class="paksa-cr-version">v' . PAKSA_CR_VERSION . '</span></h1>';
        $this->render_tabs($tab);

        match ($tab) {
            'carts'     => $this->render_carts(),
            'reports'   => $this->render_reports(),
            'emails'    => $this->render_emails(),
            'settings'  => $this->render_settings(),
            'tools'     => $this->render_tools(),
            default     => $this->render_dashboard(),
        };

        echo '</div>';
    }

    private function render_tabs(string $active): void {
        $counts = Paksa_DB::get_cart_count_by_status();
        $tabs = [
            'dashboard' => __('📊 Dashboard', 'paksa-cart-recovery'),
            'carts'     => sprintf(__('🛒 Abandoned Carts %s', 'paksa-cart-recovery'), '<span class="paksa-cr-badge">' . $counts['abandoned'] . '</span>'),
            'reports'   => __('📈 Reports', 'paksa-cart-recovery'),
            'emails'    => __('📧 Email Templates', 'paksa-cart-recovery'),
            'settings'  => __('⚙️ Settings', 'paksa-cart-recovery'),
            'tools'     => __('🧰 Tools', 'paksa-cart-recovery'),
        ];

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $slug => $label) {
            $url = admin_url("admin.php?page=paksa-cart-recovery&tab={$slug}");
            $class = $active === $slug ? 'nav-tab nav-tab-active' : 'nav-tab';
            printf('<a href="%s" class="%s">%s</a>', esc_url($url), $class, wp_kses_post($label));
        }
        echo '</nav>';
    }

    private function render_dashboard(): void {
        $stats = Paksa_DB::get_stats();
        $top_products = Paksa_DB::get_top_products(5);
        $recent = Paksa_DB::get_carts(['status' => 'abandoned', 'per_page' => 10]);
        include PAKSA_CR_PATH . 'admin/views/dashboard.php';
    }

    private function render_carts(): void {
        $status    = sanitize_text_field($_GET['status'] ?? '');
        $search    = sanitize_text_field($_GET['s'] ?? '');
        $date_from = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to   = sanitize_text_field($_GET['date_to'] ?? '');
        $paged     = max(1, (int) ($_GET['paged'] ?? 1));
        $per_page  = 20;

        $args = [
            'status'    => $status,
            'search'    => $search,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'per_page'  => $per_page,
            'offset'    => ($paged - 1) * $per_page,
        ];

        $carts = Paksa_DB::get_carts($args);
        $total = Paksa_DB::count_carts($args);
        $pages = ceil($total / $per_page);
        $whatsapp_enabled = get_option('paksa_cr_whatsapp_enabled', 'yes') === 'yes';

        include PAKSA_CR_PATH . 'admin/views/carts.php';
    }

    private function render_reports(): void {
        $period = sanitize_text_field($_GET['period'] ?? 'daily');
        $data   = Paksa_DB::get_report_data($period);
        $stats  = Paksa_DB::get_stats();
        include PAKSA_CR_PATH . 'admin/views/reports.php';
    }

    private function render_emails(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paksa_cr_save_emails'])) {
            check_admin_referer('paksa_cr_emails');
            foreach (['1h', '24h', '72h'] as $type) {
                $template = [
                    'subject' => sanitize_text_field($_POST["subject_{$type}"] ?? ''),
                    'body'    => wp_kses_post($_POST["body_{$type}"] ?? ''),
                ];
                update_option("paksa_cr_email_template_{$type}", $template);
            }
            echo '<div class="updated"><p>' . esc_html__('Email templates saved.', 'paksa-cart-recovery') . '</p></div>';
        }
        include PAKSA_CR_PATH . 'admin/views/emails.php';
    }

    private function render_settings(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paksa_cr_save_settings'])) {
            check_admin_referer('paksa_cr_settings');
            update_option('paksa_cr_abandon_timeout', absint($_POST['abandon_timeout'] ?? 30));
            update_option('paksa_cr_retention_days', absint($_POST['retention_days'] ?? 90));
            update_option('paksa_cr_token_expiry_days', absint($_POST['token_expiry_days'] ?? 7));
            update_option('paksa_cr_coupon_enabled', isset($_POST['coupon_enabled']) ? 'yes' : 'no');
            update_option('paksa_cr_coupon_type', sanitize_text_field($_POST['coupon_type'] ?? 'percent'));
            update_option('paksa_cr_coupon_amount', absint($_POST['coupon_amount'] ?? 10));
            update_option('paksa_cr_coupon_expiry', absint($_POST['coupon_expiry'] ?? 48));
            update_option('paksa_cr_coupon_min_cart', absint($_POST['coupon_min_cart'] ?? 0));
            update_option('paksa_cr_popup_enabled', isset($_POST['popup_enabled']) ? 'yes' : 'no');
            update_option('paksa_cr_popup_trigger', sanitize_text_field($_POST['popup_trigger'] ?? 'exit'));
            update_option('paksa_cr_popup_delay', absint($_POST['popup_delay'] ?? 30));
            update_option('paksa_cr_popup_heading', sanitize_text_field($_POST['popup_heading'] ?? ''));
            update_option('paksa_cr_popup_text', sanitize_text_field($_POST['popup_text'] ?? ''));
            update_option('paksa_cr_popup_button', sanitize_text_field($_POST['popup_button'] ?? ''));
            update_option('paksa_cr_whatsapp_enabled', isset($_POST['whatsapp_enabled']) ? 'yes' : 'no');
            update_option('paksa_cr_whatsapp_message', sanitize_textarea_field($_POST['whatsapp_message'] ?? ''));
            update_option('paksa_cr_email_enabled', sanitize_text_field($_POST['email_enabled'] ?? 'no'));
            update_option('paksa_cr_email_1h', isset($_POST['email_1h']) ? 'yes' : 'no');
            update_option('paksa_cr_email_24h', isset($_POST['email_24h']) ? 'yes' : 'no');
            update_option('paksa_cr_email_72h', isset($_POST['email_72h']) ? 'yes' : 'no');
            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'paksa-cart-recovery') . '</p></div>';
        }
        include PAKSA_CR_PATH . 'admin/views/settings.php';
    }

    private function render_tools(): void {
        include PAKSA_CR_PATH . 'admin/views/tools.php';
    }

    public function handle_ajax(): void {
        check_ajax_referer('paksa_cr_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        $action_type = sanitize_text_field($_POST['action_type'] ?? '');
        $cart_id     = absint($_POST['cart_id'] ?? 0);

        match ($action_type) {
            'mark_recovered' => $this->ajax_mark_recovered($cart_id),
            'delete_cart'    => $this->ajax_delete_cart($cart_id),
            'get_cart_detail'=> $this->ajax_get_cart_detail($cart_id),
            'export_csv'     => $this->ajax_export_csv(),
            'cleanup'        => $this->ajax_cleanup(),
            'bulk_delete'    => $this->ajax_bulk_delete(),
            'bulk_recover'   => $this->ajax_bulk_recover(),
            default          => wp_send_json_error('Invalid action'),
        };
    }

    private function ajax_mark_recovered(int $id): void {
        Paksa_DB::update_cart($id, ['status' => 'recovered', 'recovered_at' => current_time('mysql'), 'recovered_via' => 'manual']);
        wp_send_json_success(['message' => __('Cart marked as recovered.', 'paksa-cart-recovery')]);
    }

    private function ajax_delete_cart(int $id): void {
        Paksa_DB::delete_cart($id);
        wp_send_json_success();
    }

    private function ajax_get_cart_detail(int $id): void {
        $cart = Paksa_DB::get_cart($id);
        if (!$cart) {
            wp_send_json_error('Cart not found');
            return;
        }

        $items = maybe_unserialize($cart->cart_data);
        $html = '<div class="paksa-cr-detail">';
        $html .= '<table class="widefat"><thead><tr><th>' . __('Product', 'paksa-cart-recovery') . '</th><th>' . __('Qty', 'paksa-cart-recovery') . '</th><th>' . __('Price', 'paksa-cart-recovery') . '</th></tr></thead><tbody>';

        if (is_array($items)) {
            foreach ($items as $item) {
                $img = !empty($item['image']) ? '<img src="' . esc_url($item['image']) . '" width="40" height="40" style="border-radius:4px;vertical-align:middle;margin-right:8px;">' : '';
                $html .= '<tr>';
                $html .= '<td>' . $img . esc_html($item['name'] ?? 'Unknown') . '</td>';
                $html .= '<td>' . esc_html($item['quantity'] ?? 1) . '</td>';
                $html .= '<td>' . wc_price($item['line_total'] ?? 0) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        $html .= '<p><strong>' . __('Total:', 'paksa-cart-recovery') . '</strong> ' . wc_price($cart->cart_total) . '</p>';
        $html .= '<p><strong>' . __('Recovery Link:', 'paksa-cart-recovery') . '</strong> <code>' . esc_html(Paksa_Recovery::get_recovery_url($cart->recovery_token)) . '</code></p>';

        if ($cart->ip_address) {
            $html .= '<p><strong>' . __('IP:', 'paksa-cart-recovery') . '</strong> ' . esc_html($cart->ip_address) . '</p>';
        }
        if ($cart->created_at) {
            $html .= '<p><strong>' . __('Created:', 'paksa-cart-recovery') . '</strong> ' . esc_html($cart->created_at) . '</p>';
        }

        $html .= '</div>';

        wp_send_json_success(['html' => $html]);
    }

    private function ajax_export_csv(): void {
        $status = sanitize_text_field($_POST['export_status'] ?? '');
        $carts = Paksa_DB::get_carts(['per_page' => 99999, 'offset' => 0, 'status' => $status]);

        $rows = [['ID', 'Customer Name', 'Phone Number', 'Email', 'Cart Total', 'Items', 'Status', 'Abandoned At', 'Recovered At', 'Recovery Link']];
        foreach ($carts as $cart) {
            $items = maybe_unserialize($cart->cart_data);
            $item_names = is_array($items) ? implode(' | ', array_column($items, 'name')) : '';
            $rows[] = [
                $cart->id,
                $cart->customer_name,
                $cart->phone_number,
                $cart->email,
                $cart->cart_total,
                $item_names,
                $cart->status,
                $cart->abandoned_at ?: '',
                $cart->recovered_at ?: '',
                Paksa_Recovery::get_recovery_url($cart->recovery_token),
            ];
        }

        wp_send_json_success(['csv' => $rows, 'count' => count($carts)]);
    }

    private function ajax_cleanup(): void {
        $deleted = Paksa_DB::cleanup_old_carts();
        wp_send_json_success(['deleted' => $deleted, 'message' => sprintf(__('Deleted %d old carts.', 'paksa-cart-recovery'), $deleted)]);
    }

    private function ajax_bulk_delete(): void {
        $ids = array_map('absint', (array) ($_POST['ids'] ?? []));
        foreach ($ids as $id) {
            if ($id > 0) Paksa_DB::delete_cart($id);
        }
        wp_send_json_success(['deleted' => count($ids)]);
    }

    private function ajax_bulk_recover(): void {
        $ids = array_map('absint', (array) ($_POST['ids'] ?? []));
        foreach ($ids as $id) {
            if ($id > 0) {
                Paksa_DB::update_cart($id, ['status' => 'recovered', 'recovered_at' => current_time('mysql'), 'recovered_via' => 'manual']);
            }
        }
        wp_send_json_success(['recovered' => count($ids)]);
    }
}
