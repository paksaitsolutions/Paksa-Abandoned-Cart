<?php
defined('ABSPATH') || exit;

/**
 * Personalized Recovery Landing Page.
 * Shows customer their abandoned cart items with "Complete Order" CTA.
 * URL: ?paksa_recover=TOKEN&preview=1
 */
class Paksa_Landing_Page {

    public function __construct() {
        add_action('template_redirect', [$this, 'render_landing_page'], 5);
    }

    public function render_landing_page(): void {
        if (!isset($_GET['paksa_recover']) || !isset($_GET['preview'])) return;

        $token = sanitize_text_field($_GET['paksa_recover']);
        if (empty($token)) return;

        $cart = Paksa_DB::get_cart_by_token($token);
        if (!$cart) {
            wp_safe_redirect(wc_get_page_permalink('shop'));
            exit;
        }

        $items = maybe_unserialize($cart->cart_data);
        if (!is_array($items) || empty($items)) {
            wp_safe_redirect(wc_get_page_permalink('shop'));
            exit;
        }

        // Check expiry
        $expiry_days = (int) get_option('paksa_cr_token_expiry_days', 7);
        $abandoned_time = strtotime($cart->abandoned_at ?: $cart->created_at);
        if ((time() - $abandoned_time) > ($expiry_days * DAY_IN_SECONDS)) {
            wp_safe_redirect(wc_get_page_permalink('shop'));
            exit;
        }

        $recovery_url = Paksa_Recovery::get_recovery_url($cart->recovery_token);
        $store_name = get_bloginfo('name');
        $coupon_code = null;
        if (class_exists('Paksa_Coupon')) {
            $coupon_code = Paksa_Coupon::get_or_create_for_cart($cart);
        }

        // Render full page
        $this->output_page($cart, $items, $recovery_url, $store_name, $coupon_code);
        exit;
    }

    private function output_page(object $cart, array $items, string $recovery_url, string $store_name, ?string $coupon_code): void {
        $customer_name = $cart->customer_name ?: __('there', 'paksa-cart-recovery');
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php printf(esc_html__('Your Cart at %s', 'paksa-cart-recovery'), esc_html($store_name)); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; }
        .paksa-lp { max-width: 600px; margin: 0 auto; padding: 20px; }
        .paksa-lp-header { text-align: center; padding: 40px 20px 20px; }
        .paksa-lp-header h1 { font-size: 24px; margin-bottom: 8px; }
        .paksa-lp-header p { color: #666; font-size: 15px; }
        .paksa-lp-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 16px; overflow: hidden; }
        .paksa-lp-items { padding: 0; }
        .paksa-lp-item { display: flex; align-items: center; padding: 16px; border-bottom: 1px solid #f0f0f0; }
        .paksa-lp-item:last-child { border-bottom: none; }
        .paksa-lp-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 14px; flex-shrink: 0; }
        .paksa-lp-item-info { flex: 1; }
        .paksa-lp-item-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .paksa-lp-item-meta { font-size: 12px; color: #888; }
        .paksa-lp-item-price { font-weight: 700; font-size: 15px; text-align: right; white-space: nowrap; }
        .paksa-lp-total { display: flex; justify-content: space-between; padding: 16px 20px; font-size: 18px; font-weight: 700; background: #f9f9f9; }
        .paksa-lp-coupon { text-align: center; padding: 20px; background: #f0f9ff; border: 2px dashed #0073aa; border-radius: 12px; margin-bottom: 16px; }
        .paksa-lp-coupon-code { font-size: 24px; font-weight: 800; color: #0073aa; letter-spacing: 3px; margin: 8px 0; }
        .paksa-lp-coupon-text { font-size: 13px; color: #555; }
        .paksa-lp-cta { text-align: center; padding: 20px 0 40px; }
        .paksa-lp-btn { display: inline-block; background: #0073aa; color: #fff; padding: 16px 40px; font-size: 18px; font-weight: 700; border-radius: 8px; text-decoration: none; transition: background 0.2s; }
        .paksa-lp-btn:hover { background: #005a87; color: #fff; }
        .paksa-lp-share { text-align: center; padding: 0 0 30px; }
        .paksa-lp-share a { display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
        .paksa-lp-share-wa { background: #25d366; color: #fff; }
        .paksa-lp-share-copy { background: #e0e0e0; color: #333; cursor: pointer; }
        .paksa-lp-footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
        @media (max-width: 480px) { .paksa-lp { padding: 12px; } .paksa-lp-btn { width: 100%; text-align: center; } }
    </style>
</head>
<body>
<div class="paksa-lp">
    <div class="paksa-lp-header">
        <h1><?php printf(esc_html__('Hi %s! 👋', 'paksa-cart-recovery'), esc_html($customer_name)); ?></h1>
        <p><?php esc_html_e('You left these items in your cart. They\'re still waiting for you!', 'paksa-cart-recovery'); ?></p>
    </div>

    <div class="paksa-lp-card">
        <div class="paksa-lp-items">
            <?php foreach ($items as $item):
                $img = !empty($item['image']) ? esc_url($item['image']) : wc_placeholder_img_src();
            ?>
            <div class="paksa-lp-item">
                <img src="<?php echo $img; ?>" alt="<?php echo esc_attr($item['name'] ?? ''); ?>">
                <div class="paksa-lp-item-info">
                    <div class="paksa-lp-item-name"><?php echo esc_html($item['name'] ?? 'Product'); ?></div>
                    <div class="paksa-lp-item-meta"><?php printf(esc_html__('Qty: %d', 'paksa-cart-recovery'), (int)($item['quantity'] ?? 1)); ?></div>
                </div>
                <div class="paksa-lp-item-price"><?php echo wc_price($item['line_total'] ?? 0); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="paksa-lp-total">
            <span><?php esc_html_e('Total', 'paksa-cart-recovery'); ?></span>
            <span><?php echo wc_price($cart->cart_total); ?></span>
        </div>
    </div>

    <?php if ($coupon_code): ?>
    <div class="paksa-lp-coupon">
        <div class="paksa-lp-coupon-text"><?php esc_html_e('🎁 Special Discount For You!', 'paksa-cart-recovery'); ?></div>
        <div class="paksa-lp-coupon-code"><?php echo esc_html($coupon_code); ?></div>
        <div class="paksa-lp-coupon-text"><?php echo esc_html(Paksa_Coupon::get_coupon_message($coupon_code)); ?></div>
    </div>
    <?php endif; ?>

    <div class="paksa-lp-cta">
        <a href="<?php echo esc_url($recovery_url); ?>" class="paksa-lp-btn"><?php esc_html_e('🛒 Complete My Order', 'paksa-cart-recovery'); ?></a>
    </div>

    <div class="paksa-lp-share">
        <p style="margin-bottom:10px;font-size:13px;color:#666;"><?php esc_html_e('Share this cart:', 'paksa-cart-recovery'); ?></p>
        <?php
        $share_url = add_query_arg(['paksa_recover' => $cart->recovery_token, 'preview' => '1'], home_url('/'));
        $wa_text = rawurlencode(sprintf(__('Check out my cart at %s: %s', 'paksa-cart-recovery'), $store_name, $share_url));
        ?>
        <a href="https://wa.me/?text=<?php echo $wa_text; ?>" target="_blank" class="paksa-lp-share-wa">💬 WhatsApp</a>
        <a href="#" class="paksa-lp-share-copy" onclick="navigator.clipboard.writeText('<?php echo esc_js($share_url); ?>');this.textContent='✓ Copied!';return false;">📋 Copy Link</a>
    </div>

    <div class="paksa-lp-footer">
        <?php echo esc_html($store_name); ?> &bull; <?php esc_html_e('Secure Checkout', 'paksa-cart-recovery'); ?>
    </div>
</div>
</body>
</html>
        <?php
    }
}
