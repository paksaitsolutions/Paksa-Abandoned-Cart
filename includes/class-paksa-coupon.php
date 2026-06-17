<?php
defined('ABSPATH') || exit;

/**
 * Generates unique recovery coupons to incentivize cart completion.
 */
class Paksa_Coupon {

    public function __construct() {
        // Clean expired recovery coupons daily
        add_action('paksa_cr_check_abandoned', [$this, 'cleanup_expired_coupons']);
    }

    /**
     * Generate a unique coupon for a specific abandoned cart.
     */
    public static function generate_for_cart(object $cart): ?string {
        if (get_option('paksa_cr_coupon_enabled', 'no') !== 'yes') {
            return null;
        }

        $discount_type   = get_option('paksa_cr_coupon_type', 'percent');
        $discount_amount = (float) get_option('paksa_cr_coupon_amount', 10);
        $expiry_hours    = (int) get_option('paksa_cr_coupon_expiry', 48);
        $min_cart_value  = (float) get_option('paksa_cr_coupon_min_cart', 0);

        // Don't generate if cart below minimum
        if ($min_cart_value > 0 && $cart->cart_total < $min_cart_value) {
            return null;
        }

        $code = 'PAKSA-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($discount_amount);
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);
        $coupon->set_date_expires(time() + ($expiry_hours * HOUR_IN_SECONDS));

        if ($min_cart_value > 0) {
            $coupon->set_minimum_amount($min_cart_value);
        }

        // Restrict to customer email if available
        if (!empty($cart->email)) {
            $coupon->set_email_restrictions([$cart->email]);
        }

        // Mark as paksa recovery coupon for cleanup
        $coupon->add_meta_data('_paksa_cr_coupon', '1', true);
        $coupon->add_meta_data('_paksa_cr_cart_id', $cart->id, true);
        $coupon->save();

        return $code;
    }

    /**
     * Get coupon code for a cart (generate if not exists).
     */
    public static function get_or_create_for_cart(object $cart): ?string {
        if (get_option('paksa_cr_coupon_enabled', 'no') !== 'yes') {
            return null;
        }

        // Check if we already generated one for this cart
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT p.post_title FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = '_paksa_cr_cart_id' AND pm.meta_value = %d
             AND p.post_type = 'shop_coupon' AND p.post_status = 'publish'
             LIMIT 1",
            $cart->id
        ));

        if ($existing) {
            // Verify it hasn't expired
            $coupon = new WC_Coupon($existing);
            $expiry = $coupon->get_date_expires();
            if ($expiry && $expiry->getTimestamp() > time()) {
                return $existing;
            }
            // Expired, delete and create new
            $coupon->delete(true);
        }

        return self::generate_for_cart($cart);
    }

    /**
     * Get formatted coupon message for WhatsApp/email.
     */
    public static function get_coupon_message(string $code): string {
        $discount_type   = get_option('paksa_cr_coupon_type', 'percent');
        $discount_amount = (float) get_option('paksa_cr_coupon_amount', 10);
        $expiry_hours    = (int) get_option('paksa_cr_coupon_expiry', 48);

        $discount_text = $discount_type === 'percent'
            ? $discount_amount . '%'
            : strip_tags(wc_price($discount_amount));

        return sprintf(
            __('Use code %1$s to get %2$s OFF! Valid for %3$d hours only.', 'paksa-cart-recovery'),
            $code,
            $discount_text,
            $expiry_hours
        );
    }

    /**
     * Delete expired paksa recovery coupons.
     */
    public function cleanup_expired_coupons(): void {
        global $wpdb;

        $expired_ids = $wpdb->get_col(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
             WHERE pm.meta_key = '_paksa_cr_coupon' AND pm.meta_value = '1'
             AND pm2.meta_key = 'date_expires' AND pm2.meta_value < UNIX_TIMESTAMP()
             AND pm2.meta_value != ''
             AND p.post_type = 'shop_coupon'
             LIMIT 50"
        );

        foreach ($expired_ids as $id) {
            wp_delete_post((int) $id, true);
        }
    }
}
