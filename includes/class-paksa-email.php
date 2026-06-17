<?php
defined('ABSPATH') || exit;

class Paksa_Email {

    public static function send_recovery(object $cart, string $type): bool {
        if (empty($cart->email) || !is_email($cart->email)) return false;

        $recovery_url = Paksa_Recovery::get_recovery_url($cart->recovery_token);
        $template     = self::get_template($type);
        $subject      = $template['subject'];
        $body         = $template['body'];

        // Build product list HTML
        $items = maybe_unserialize($cart->cart_data);
        $products_html = self::build_products_html($items);

        // Generate coupon if enabled
        $coupon_code = Paksa_Coupon::get_or_create_for_cart($cart);
        $coupon_html = $coupon_code ? self::build_coupon_html($coupon_code) : '';

        // Replace placeholders
        $replacements = [
            '{customer_name}' => $cart->customer_name ?: __('Customer', 'paksa-cart-recovery'),
            '{recovery_link}' => $recovery_url,
            '{cart_total}'    => strip_tags(wc_price($cart->cart_total)),
            '{store_name}'    => get_bloginfo('name'),
            '{store_url}'     => home_url(),
            '{products_list}' => $products_html,
            '{items_count}'   => $cart->cart_items_count ?: count($items ?: []),
            '{coupon_code}'   => $coupon_code ?: '',
            '{coupon_block}'  => $coupon_html,
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $body    = str_replace(array_keys($replacements), array_values($replacements), $body);

        $from_name  = get_bloginfo('name');
        $from_email = get_option('admin_email');
        $headers    = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        ];

        return wp_mail($cart->email, $subject, $body, $headers);
    }

    private static function build_coupon_html(string $code): string {
        $message = Paksa_Coupon::get_coupon_message($code);
        return '<div style="background:#f0f9ff;border:2px dashed #0073aa;border-radius:8px;padding:16px;margin:16px 0;text-align:center;">'
            . '<p style="margin:0 0 8px;font-size:13px;color:#555;">' . esc_html__('Special Discount For You:', 'paksa-cart-recovery') . '</p>'
            . '<p style="margin:0;font-size:22px;font-weight:700;color:#0073aa;letter-spacing:2px;">' . esc_html($code) . '</p>'
            . '<p style="margin:8px 0 0;font-size:12px;color:#666;">' . esc_html($message) . '</p>'
            . '</div>';
    }

    private static function build_products_html(?array $items): string {
        if (!$items) return '';

        $html = '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
        foreach ($items as $item) {
            $name  = esc_html($item['name'] ?? 'Product');
            $qty   = (int) ($item['quantity'] ?? 1);
            $price = strip_tags(wc_price($item['line_total'] ?? 0));
            $image = esc_url($item['image'] ?? '');
            $url   = esc_url($item['url'] ?? '#');

            $html .= '<tr style="border-bottom:1px solid #eee;">';
            if ($image) {
                $html .= "<td style=\"padding:8px;width:60px;\"><img src=\"{$image}\" width=\"50\" height=\"50\" style=\"border-radius:4px;object-fit:cover;\"></td>";
            }
            $html .= "<td style=\"padding:8px;\"><a href=\"{$url}\" style=\"text-decoration:none;color:#333;font-weight:600;\">{$name}</a><br><small style=\"color:#666;\">Qty: {$qty}</small></td>";
            $html .= "<td style=\"padding:8px;text-align:right;font-weight:600;\">{$price}</td>";
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public static function get_template(string $type): array {
        $saved = get_option("paksa_cr_email_template_{$type}", []);
        if (!empty($saved['subject']) && !empty($saved['body'])) {
            return $saved;
        }
        return self::default_template($type);
    }

    public static function default_template(string $type): array {
        return match ($type) {
            '1h' => [
                'subject' => __("You left something behind at {store_name}!", 'paksa-cart-recovery'),
                'body'    => self::wrap_html(
                    "<h2 style=\"color:#333;\">" . __('Did you forget something?', 'paksa-cart-recovery') . "</h2>"
                    . "<p style=\"font-size:16px;color:#555;\">" . __('Hi {customer_name}, you left items worth <strong>{cart_total}</strong> in your cart.', 'paksa-cart-recovery') . "</p>"
                    . "{products_list}"
                    . "<p style=\"text-align:center;margin:24px 0;\"><a href=\"{recovery_link}\" style=\"background:#0073aa;color:#fff;padding:14px 32px;text-decoration:none;border-radius:6px;display:inline-block;font-size:16px;font-weight:600;\">"
                    . __('Complete Your Order', 'paksa-cart-recovery') . "</a></p>"
                    . "<p style=\"color:#888;font-size:13px;\">" . __('This link will expire in 7 days.', 'paksa-cart-recovery') . "</p>"
                ),
            ],
            '24h' => [
                'subject' => __("Your cart is waiting at {store_name}", 'paksa-cart-recovery'),
                'body'    => self::wrap_html(
                    "<h2 style=\"color:#333;\">" . __('Your items are still available!', 'paksa-cart-recovery') . "</h2>"
                    . "<p style=\"font-size:16px;color:#555;\">" . __('Hi {customer_name}, your cart worth <strong>{cart_total}</strong> is waiting for you. Don\'t miss out!', 'paksa-cart-recovery') . "</p>"
                    . "{products_list}"
                    . "<p style=\"text-align:center;margin:24px 0;\"><a href=\"{recovery_link}\" style=\"background:#0073aa;color:#fff;padding:14px 32px;text-decoration:none;border-radius:6px;display:inline-block;font-size:16px;font-weight:600;\">"
                    . __('Recover My Cart', 'paksa-cart-recovery') . "</a></p>"
                ),
            ],
            '72h' => [
                'subject' => __("Last chance! Your cart at {store_name} is expiring", 'paksa-cart-recovery'),
                'body'    => self::wrap_html(
                    "<h2 style=\"color:#d63638;\">" . __('⏰ Last Reminder!', 'paksa-cart-recovery') . "</h2>"
                    . "<p style=\"font-size:16px;color:#555;\">" . __('Hi {customer_name}, your cart worth <strong>{cart_total}</strong> will expire very soon. This is your last chance to complete your order.', 'paksa-cart-recovery') . "</p>"
                    . "{products_list}"
                    . "<p style=\"text-align:center;margin:24px 0;\"><a href=\"{recovery_link}\" style=\"background:#d63638;color:#fff;padding:14px 32px;text-decoration:none;border-radius:6px;display:inline-block;font-size:16px;font-weight:600;\">"
                    . __('Order Now Before It Expires', 'paksa-cart-recovery') . "</a></p>"
                ),
            ],
            default => ['subject' => '', 'body' => ''],
        };
    }

    private static function wrap_html(string $content): string {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:600px;margin:0 auto;padding:20px;background:#f7f7f7;">'
            . '<div style="background:#ffffff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">'
            . $content
            . '</div>'
            . '<p style="margin-top:24px;font-size:12px;color:#999;text-align:center;">{store_name} &bull; <a href="{store_url}" style="color:#999;">{store_url}</a></p>'
            . '</body></html>';
    }
}
