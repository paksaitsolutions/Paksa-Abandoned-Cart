<?php
defined('ABSPATH') || exit;

class Paksa_Tracker {

    public function __construct() {
        // AJAX for checkout field capture
        add_action('wp_ajax_paksa_cr_save_checkout', [$this, 'save_checkout_data']);
        add_action('wp_ajax_nopriv_paksa_cr_save_checkout', [$this, 'save_checkout_data']);

        // AJAX for cart page tracking
        add_action('wp_ajax_paksa_cr_track_cart', [$this, 'track_cart_ajax']);
        add_action('wp_ajax_nopriv_paksa_cr_track_cart', [$this, 'track_cart_ajax']);

        // Cart change hooks
        add_action('woocommerce_add_to_cart', [$this, 'on_cart_update'], 99);
        add_action('woocommerce_cart_item_removed', [$this, 'on_cart_update'], 99);
        add_action('woocommerce_after_cart_item_quantity_update', [$this, 'on_cart_update'], 99);
        add_action('woocommerce_cart_item_restored', [$this, 'on_cart_update'], 99);

        // Order placed — mark recovered
        add_action('woocommerce_checkout_order_processed', [$this, 'on_order_placed'], 10, 1);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'on_order_placed_block'], 10, 1);

        // Cart emptied
        add_action('woocommerce_cart_emptied', [$this, 'on_cart_emptied']);

        // Start session cookie early
        add_action('wp_loaded', [$this, 'ensure_session'], 1);
    }

    public function ensure_session(): void {
        if (is_admin() || wp_doing_cron() || wp_doing_ajax()) return;
        if (headers_sent()) return;
        $this->get_session_id();
    }

    private function get_session_id(): string {
        if (!empty($_COOKIE['paksa_cr_session'])) {
            return sanitize_text_field($_COOKIE['paksa_cr_session']);
        }

        // Fallback: use WC session ID if available
        $session_id = '';
        if (function_exists('WC') && WC()->session && method_exists(WC()->session, 'get_customer_id')) {
            $wc_id = WC()->session->get_customer_id();
            if ($wc_id) {
                $session_id = 'wc_' . $wc_id;
            }
        }

        if (empty($session_id)) {
            $session_id = bin2hex(random_bytes(16));
        }

        if (!headers_sent()) {
            setcookie('paksa_cr_session', $session_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
        $_COOKIE['paksa_cr_session'] = $session_id;
        return $session_id;
    }

    private function get_cart_items(): array {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) return [];

        $items = [];
        foreach (WC()->cart->get_cart() as $item) {
            $product = $item['data'] ?? null;
            if (!$product || !($product instanceof WC_Product)) continue;

            $items[] = [
                'product_id'   => $item['product_id'],
                'variation_id' => $item['variation_id'] ?? 0,
                'name'         => $product->get_name(),
                'sku'          => $product->get_sku(),
                'quantity'     => $item['quantity'],
                'price'        => (float) $product->get_price(),
                'line_total'   => (float) ($item['quantity'] * $product->get_price()),
                'image'        => wp_get_attachment_url($product->get_image_id()) ?: wc_placeholder_img_src(),
                'url'          => get_permalink($item['product_id']),
            ];
        }
        return $items;
    }

    private function get_cart_items_count(): int {
        return (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    }

    private function get_client_ip(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

    private function find_existing_cart(): ?object {
        $user_id    = get_current_user_id();
        $session_id = $this->get_session_id();

        if ($user_id) {
            return Paksa_DB::get_active_cart_by_user($user_id);
        }
        return Paksa_DB::get_active_cart_by_session($session_id);
    }

    public function on_cart_update(): void {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) return;

        // Prevent recursion from hooks triggered during this method
        static $running = false;
        if ($running) return;
        $running = true;

        $session_id = $this->get_session_id();
        $user_id    = get_current_user_id();
        $items      = $this->get_cart_items();
        $total      = (float) WC()->cart->get_total('edit');

        $existing = $this->find_existing_cart();

        $data = [
            'cart_data'        => maybe_serialize($items),
            'cart_total'       => $total,
            'cart_items_count' => $this->get_cart_items_count(),
            'status'           => 'active',
        ];

        if ($existing) {
            Paksa_DB::update_cart($existing->id, $data);
        } else {
            $data['session_id']  = $session_id;
            $data['user_id']     = $user_id;
            $data['ip_address']  = $this->get_client_ip();
            $data['user_agent']  = substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            Paksa_DB::insert_cart($data);
        }

        $running = false;
    }

    public function track_cart_ajax(): void {
        check_ajax_referer('paksa_cr_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            wp_send_json_success(['tracked' => false]);
            return;
        }

        $this->on_cart_update();
        wp_send_json_success(['tracked' => true]);
    }

    public function save_checkout_data(): void {
        check_ajax_referer('paksa_cr_nonce', 'nonce');

        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $name  = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 7) {
            wp_send_json_error('Valid phone number required');
            return;
        }

        $session_id = $this->get_session_id();
        $user_id    = get_current_user_id();
        $items      = $this->get_cart_items();
        $total      = (function_exists('WC') && WC()->cart) ? (float) WC()->cart->get_total('edit') : 0;

        // Try to find existing by user > session > phone
        $existing = $this->find_existing_cart();
        if (!$existing) {
            $existing = Paksa_DB::get_active_cart_by_phone($phone);
        }

        $data = [
            'customer_name'    => $name,
            'phone_number'     => $phone,
            'email'            => $email,
            'cart_data'        => maybe_serialize($items),
            'cart_total'       => $total,
            'cart_items_count' => $this->get_cart_items_count(),
        ];

        if ($existing) {
            Paksa_DB::update_cart($existing->id, $data);
        } else {
            $data['session_id']  = $session_id;
            $data['user_id']     = $user_id;
            $data['status']      = 'active';
            $data['ip_address']  = $this->get_client_ip();
            $data['user_agent']  = substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            Paksa_DB::insert_cart($data);
        }

        wp_send_json_success();
    }

    public function on_order_placed(int $order_id): void {
        $this->mark_recovered_from_order($order_id, 'checkout');
    }

    public function on_order_placed_block($order): void {
        $order_id = is_object($order) ? $order->get_id() : (int) $order;
        $this->mark_recovered_from_order($order_id, 'block_checkout');
    }

    private function mark_recovered_from_order(int $order_id, string $via): void {
        $existing = $this->find_existing_cart();

        // Also try by phone from order
        if (!$existing && $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $phone = $order->get_billing_phone();
                if ($phone) {
                    $existing = Paksa_DB::get_active_cart_by_phone($phone);
                }
            }
        }

        if ($existing) {
            Paksa_DB::update_cart($existing->id, [
                'status'        => 'recovered',
                'recovered_at'  => current_time('mysql'),
                'recovered_via' => $via,
            ]);

            // Fire webhook
            Paksa_Webhooks::fire_recovered((int) $existing->id);

            // Add order note
            $order = wc_get_order($order_id);
            if ($order) {
                $note = sprintf(
                    __('♻️ Recovered from abandoned cart #%d via %s. Cart was abandoned %s.', 'paksa-cart-recovery'),
                    $existing->id,
                    $via,
                    $existing->abandoned_at ? human_time_diff(strtotime($existing->abandoned_at)) . ' ago' : 'N/A'
                );
                $order->add_order_note($note);
                $order->update_meta_data('_paksa_cr_recovered', '1');
                $order->update_meta_data('_paksa_cr_cart_id', $existing->id);
                $order->save();
            }
        }
    }

    public function on_cart_emptied(): void {
        $existing = $this->find_existing_cart();
        if ($existing) {
            Paksa_DB::delete_cart($existing->id);
        }
    }
}
