<?php
defined('ABSPATH') || exit;

class Paksa_Recovery {

    public static function handle_recovery_link(): void {
        if (!isset($_GET['paksa_recover'])) return;

        $token = sanitize_text_field($_GET['paksa_recover']);
        if (empty($token)) return;

        // Ensure WC cart is loaded
        if (!function_exists('WC') || !WC()->cart) {
            if (function_exists('wc_load_cart')) {
                wc_load_cart();
            } else {
                // Fallback for older WC
                WC()->frontend_includes();
                WC()->cart = new WC_Cart();
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
        }

        $cart = Paksa_DB::get_cart_by_token($token);
        if (!$cart) {
            wc_add_notice(__('This recovery link is invalid or has already been used.', 'paksa-cart-recovery'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        // Check expiry
        $expiry_days = (int) get_option('paksa_cr_token_expiry_days', 7);
        $abandoned_time = strtotime($cart->abandoned_at ?: $cart->created_at);
        if ((time() - $abandoned_time) > ($expiry_days * DAY_IN_SECONDS)) {
            Paksa_DB::update_cart($cart->id, ['status' => 'expired']);
            wc_add_notice(__('This recovery link has expired. Please add items to your cart again.', 'paksa-cart-recovery'), 'error');
            wp_safe_redirect(wc_get_page_permalink('shop'));
            exit;
        }

        // Already recovered
        if ($cart->status === 'recovered') {
            wc_add_notice(__('This cart has already been recovered.', 'paksa-cart-recovery'), 'notice');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        // Restore cart items
        $items = maybe_unserialize($cart->cart_data);
        if (!is_array($items) || empty($items)) {
            wc_add_notice(__('Cart data is empty or corrupted.', 'paksa-cart-recovery'), 'error');
            wp_safe_redirect(wc_get_page_permalink('shop'));
            exit;
        }

        WC()->cart->empty_cart();
        $restored = 0;
        $unavailable = [];

        foreach ($items as $item) {
            $product_id   = (int) ($item['product_id'] ?? 0);
            $variation_id = (int) ($item['variation_id'] ?? 0);
            $quantity     = max(1, (int) ($item['quantity'] ?? 1));

            if (!$product_id) continue;

            $product = wc_get_product($variation_id ?: $product_id);
            if (!$product || !$product->is_purchasable()) {
                $unavailable[] = $item['name'] ?? "Product #{$product_id}";
                continue;
            }

            // Check stock
            if (!$product->is_in_stock()) {
                $unavailable[] = $product->get_name();
                continue;
            }

            // Adjust quantity if limited stock
            if ($product->managing_stock()) {
                $stock = $product->get_stock_quantity();
                if ($stock < $quantity) {
                    $quantity = max(1, $stock);
                }
            }

            try {
                WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
                $restored++;
            } catch (\Exception $e) {
                $unavailable[] = $item['name'] ?? "Product #{$product_id}";
            }
        }

        // Mark recovered
        Paksa_DB::update_cart($cart->id, [
            'status'        => 'recovered',
            'recovered_at'  => current_time('mysql'),
            'recovered_via' => 'link',
        ]);

        // Notify customer
        if ($restored > 0) {
            wc_add_notice(__('Your cart has been restored successfully!', 'paksa-cart-recovery'), 'success');
        }
        if (!empty($unavailable)) {
            wc_add_notice(
                sprintf(__('Some items are no longer available: %s', 'paksa-cart-recovery'), implode(', ', $unavailable)),
                'notice'
            );
        }

        // Pre-fill checkout fields if we have customer data
        if ($cart->customer_name || $cart->phone_number || $cart->email) {
            if (WC()->session) {
                WC()->session->set('paksa_cr_customer', [
                    'name'  => $cart->customer_name,
                    'phone' => $cart->phone_number,
                    'email' => $cart->email,
                ]);
            }
            add_filter('woocommerce_checkout_get_value', [self::class, 'prefill_checkout'], 10, 2);
        }

        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

    public static function prefill_checkout($value, string $input) {
        if (!function_exists('WC') || !WC()->session) return $value;

        $customer = WC()->session->get('paksa_cr_customer');
        if (!$customer || !is_array($customer)) return $value;

        $name_parts = explode(' ', trim($customer['name'] ?? ''), 2);

        return match ($input) {
            'billing_phone'      => !empty($customer['phone']) ? $customer['phone'] : $value,
            'billing_email'      => !empty($customer['email']) ? $customer['email'] : $value,
            'billing_first_name' => !empty($name_parts[0]) ? $name_parts[0] : $value,
            'billing_last_name'  => !empty($name_parts[1]) ? $name_parts[1] : $value,
            default              => $value,
        };
    }

    public static function get_recovery_url(string $token): string {
        return add_query_arg('paksa_recover', $token, home_url('/'));
    }

    public static function get_whatsapp_url(object $cart): string {
        $phone   = Paksa_DB::phone_for_whatsapp($cart->phone_number);
        $message = get_option('paksa_cr_whatsapp_message', '');

        // Generate coupon if enabled
        $coupon_code = Paksa_Coupon::get_or_create_for_cart($cart);
        $coupon_text = $coupon_code ? "\n" . Paksa_Coupon::get_coupon_message($coupon_code) : '';

        $replacements = [
            '{customer_name}' => $cart->customer_name ?: __('Customer', 'paksa-cart-recovery'),
            '{recovery_link}' => self::get_recovery_url($cart->recovery_token),
            '{cart_total}'    => strip_tags(wc_price($cart->cart_total)),
            '{store_name}'    => get_bloginfo('name'),
            '{coupon_code}'   => $coupon_code ?: '',
            '{coupon_text}'   => $coupon_text,
        ];
        $message = str_replace(array_keys($replacements), array_values($replacements), $message);

        // Append coupon if placeholder not in template
        if ($coupon_text && strpos($message, $coupon_code) === false) {
            $message .= $coupon_text;
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }
}
