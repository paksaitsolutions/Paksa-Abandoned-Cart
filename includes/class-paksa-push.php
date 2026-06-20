<?php
defined('ABSPATH') || exit;

/**
 * Browser Push Notifications for cart recovery.
 * Uses native Web Push API — no third-party services needed.
 */
class Paksa_Push {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_paksa_cr_push_subscribe', [$this, 'save_subscription']);
        add_action('wp_ajax_nopriv_paksa_cr_push_subscribe', [$this, 'save_subscription']);
        add_action('paksa_cr_check_abandoned', [$this, 'send_push_notifications']);
        add_action('wp_head', [$this, 'register_service_worker_route']);
    }

    public function enqueue_scripts(): void {
        if (get_option('paksa_cr_push_enabled', 'no') !== 'yes') return;
        if (is_admin()) return;

        wp_enqueue_script('paksa-cr-push', PAKSA_CR_URL . 'assets/js/push.js', [], PAKSA_CR_VERSION, true);
        wp_localize_script('paksa-cr-push', 'paksa_cr_push', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('paksa_cr_push_nonce'),
            'sw_url'   => home_url('/?paksa_cr_sw=1'),
            'vapid_public' => get_option('paksa_cr_push_vapid_public', ''),
        ]);
    }

    /**
     * Serve the service worker JS via a query parameter route.
     */
    public function register_service_worker_route(): void {
        if (!isset($_GET['paksa_cr_sw'])) return;

        header('Content-Type: application/javascript');
        header('Service-Worker-Allowed: /');
        echo $this->get_service_worker_js();
        exit;
    }

    private function get_service_worker_js(): string {
        $icon = get_site_icon_url(192) ?: '';
        return <<<JS
self.addEventListener('push', function(event) {
    var data = event.data ? event.data.json() : {};
    var title = data.title || 'You left something behind!';
    var options = {
        body: data.body || 'Your cart is waiting for you.',
        icon: data.icon || '{$icon}',
        badge: data.icon || '{$icon}',
        data: { url: data.url || '/' },
        requireInteraction: true,
        actions: [{ action: 'open', title: 'Complete Order' }]
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    var url = event.notification.data.url || '/';
    event.waitUntil(clients.openWindow(url));
});
JS;
    }

    /**
     * Save push subscription from browser.
     */
    public function save_subscription(): void {
        check_ajax_referer('paksa_cr_push_nonce', 'nonce');

        $subscription = sanitize_text_field($_POST['subscription'] ?? '');
        if (empty($subscription)) {
            wp_send_json_error('No subscription data');
            return;
        }

        // Store subscription linked to session
        $session_id = sanitize_text_field($_COOKIE['paksa_cr_session'] ?? '');
        if (empty($session_id)) {
            wp_send_json_error('No session');
            return;
        }

        // Store in user meta or options keyed by session
        $subscriptions = get_option('paksa_cr_push_subscriptions', []);
        if (!is_array($subscriptions)) $subscriptions = [];
        $subscriptions[$session_id] = $subscription;

        // Keep max 500 subscriptions
        if (count($subscriptions) > 500) {
            $subscriptions = array_slice($subscriptions, -500, 500, true);
        }
        update_option('paksa_cr_push_subscriptions', $subscriptions);

        wp_send_json_success();
    }

    /**
     * Send push notifications to abandoned cart owners.
     */
    public function send_push_notifications(): void {
        if (get_option('paksa_cr_push_enabled', 'no') !== 'yes') return;

        global $wpdb;
        $table = Paksa_DB::table();
        $now = current_time('mysql');

        // Get recently abandoned carts (within last 1 hour, not yet push-notified)
        $carts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status = 'abandoned'
               AND abandoned_at >= DATE_SUB(%s, INTERVAL 1 HOUR)
               AND session_id != ''
             ORDER BY abandoned_at DESC
             LIMIT 20",
            $now
        ));

        if (empty($carts)) return;

        $subscriptions = get_option('paksa_cr_push_subscriptions', []);
        if (empty($subscriptions) || !is_array($subscriptions)) return;

        $store_name = get_bloginfo('name');

        foreach ($carts as $cart) {
            if (!isset($subscriptions[$cart->session_id])) continue;

            $sub_data = json_decode($subscriptions[$cart->session_id], true);
            if (!$sub_data || empty($sub_data['endpoint'])) continue;

            $recovery_url = Paksa_Recovery::get_recovery_url($cart->recovery_token);

            $payload = wp_json_encode([
                'title' => sprintf(__('You left %s in your cart!', 'paksa-cart-recovery'), strip_tags(wc_price($cart->cart_total))),
                'body'  => sprintf(__('Complete your order at %s. Tap to recover your cart.', 'paksa-cart-recovery'), $store_name),
                'url'   => $recovery_url,
                'icon'  => get_site_icon_url(192),
            ]);

            $this->send_web_push($sub_data, $payload);

            // Remove subscription after sending to avoid spam
            unset($subscriptions[$cart->session_id]);
        }

        update_option('paksa_cr_push_subscriptions', $subscriptions);
    }

    /**
     * Send a web push message (simplified — works without VAPID for same-origin).
     */
    private function send_web_push(array $subscription, string $payload): void {
        $endpoint = $subscription['endpoint'] ?? '';
        if (empty($endpoint)) return;

        wp_remote_post($endpoint, [
            'body'    => $payload,
            'headers' => [
                'Content-Type'     => 'application/json',
                'TTL'              => '86400',
            ],
            'timeout' => 10,
            'blocking' => false,
        ]);
    }
}
