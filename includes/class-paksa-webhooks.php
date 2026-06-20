<?php
defined('ABSPATH') || exit;

/**
 * Fires webhooks on cart events for Zapier/n8n/custom integrations.
 */
class Paksa_Webhooks {

    public function __construct() {
        add_action('paksa_cr_cart_abandoned', [$this, 'on_abandoned'], 10, 1);
        add_action('paksa_cr_cart_recovered', [$this, 'on_recovered'], 10, 1);
    }

    /**
     * Fire when a cart is marked as abandoned.
     */
    public function on_abandoned(int $cart_id): void {
        $url = get_option('paksa_cr_webhook_abandoned', '');
        if (empty($url)) return;

        $cart = Paksa_DB::get_cart($cart_id);
        if (!$cart) return;

        $this->send($url, 'cart.abandoned', $cart);
    }

    /**
     * Fire when a cart is recovered.
     */
    public function on_recovered(int $cart_id): void {
        $url = get_option('paksa_cr_webhook_recovered', '');
        if (empty($url)) return;

        $cart = Paksa_DB::get_cart($cart_id);
        if (!$cart) return;

        $this->send($url, 'cart.recovered', $cart);
    }

    /**
     * Send webhook payload.
     */
    private function send(string $url, string $event, object $cart): void {
        $items = maybe_unserialize($cart->cart_data);

        $payload = [
            'event'         => $event,
            'cart_id'       => (int) $cart->id,
            'customer_name' => $cart->customer_name,
            'phone_number'  => $cart->phone_number,
            'email'         => $cart->email,
            'cart_total'    => (float) $cart->cart_total,
            'items_count'   => (int) $cart->cart_items_count,
            'items'         => is_array($items) ? array_map(function($i) {
                return [
                    'product_id' => $i['product_id'] ?? 0,
                    'name'       => $i['name'] ?? '',
                    'quantity'   => $i['quantity'] ?? 1,
                    'price'      => $i['price'] ?? 0,
                ];
            }, $items) : [],
            'location'      => $cart->location ?? '',
            'recovery_url'  => Paksa_Recovery::get_recovery_url($cart->recovery_token),
            'abandoned_at'  => $cart->abandoned_at,
            'recovered_at'  => $cart->recovered_at,
            'timestamp'     => current_time('c'),
        ];

        wp_remote_post($url, [
            'body'      => wp_json_encode($payload),
            'headers'   => [
                'Content-Type'       => 'application/json',
                'X-Paksa-Event'      => $event,
                'X-Paksa-Signature'  => hash_hmac('sha256', wp_json_encode($payload), wp_salt('auth')),
            ],
            'timeout'   => 10,
            'blocking'  => false, // Non-blocking — don't slow down the site
        ]);
    }

    /**
     * Trigger the abandoned webhook. Called from cron after marking carts abandoned.
     */
    public static function fire_abandoned(int $cart_id): void {
        do_action('paksa_cr_cart_abandoned', $cart_id);
    }

    /**
     * Trigger the recovered webhook.
     */
    public static function fire_recovered(int $cart_id): void {
        do_action('paksa_cr_cart_recovered', $cart_id);
    }
}
