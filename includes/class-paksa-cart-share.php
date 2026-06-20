<?php
defined('ABSPATH') || exit;

/**
 * Cart Sharing — lets customers or admins share a cart via WhatsApp/link.
 * The shared link shows the personalized landing page with "Complete Order" button.
 */
class Paksa_Cart_Share {

    public function __construct() {
        add_action('wp_ajax_paksa_cr_share_cart', [$this, 'generate_share_link']);
        add_action('wp_ajax_nopriv_paksa_cr_share_cart', [$this, 'generate_share_link']);
        add_shortcode('paksa_share_cart', [$this, 'share_button_shortcode']);
        add_action('woocommerce_cart_actions', [$this, 'add_share_button_to_cart']);
    }

    /**
     * Add "Share Cart" button on WooCommerce cart page.
     */
    public function add_share_button_to_cart(): void {
        if (get_option('paksa_cr_share_enabled', 'yes') !== 'yes') return;
        ?>
        <button type="button" class="button paksa-cr-share-cart-btn" id="paksa-cr-share-cart">
            <?php esc_html_e('📤 Share Cart', 'paksa-cart-recovery'); ?>
        </button>
        <div id="paksa-cr-share-result" style="display:none;margin-top:10px;padding:12px;background:#f0f9ff;border-radius:6px;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:600;"><?php esc_html_e('Share your cart:', 'paksa-cart-recovery'); ?></p>
            <input type="text" id="paksa-cr-share-url" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;margin-bottom:8px;" readonly>
            <a id="paksa-cr-share-wa" href="#" target="_blank" style="display:inline-block;background:#25d366;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-size:13px;font-weight:600;">💬 WhatsApp</a>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('paksa-cr-share-url').value);this.textContent='✓ Copied!'" style="padding:8px 16px;border:1px solid #ddd;border-radius:4px;background:#fff;cursor:pointer;font-size:13px;">📋 Copy</button>
        </div>
        <script>
        document.getElementById('paksa-cr-share-cart').addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.textContent = '⏳ Generating...';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                btn.disabled = false;
                btn.textContent = '📤 Share Cart';
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    document.getElementById('paksa-cr-share-url').value = res.data.share_url;
                    document.getElementById('paksa-cr-share-wa').href = res.data.whatsapp_url;
                    document.getElementById('paksa-cr-share-result').style.display = 'block';
                }
            };
            xhr.send('action=paksa_cr_share_cart&nonce=<?php echo wp_create_nonce('paksa_cr_nonce'); ?>');
        });
        </script>
        <?php
    }

    /**
     * AJAX: Generate a shareable link for the current cart.
     */
    public function generate_share_link(): void {
        check_ajax_referer('paksa_cr_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            wp_send_json_error('Cart is empty');
            return;
        }

        // Find or create a tracked cart for this session
        $user_id = get_current_user_id();
        $session_id = sanitize_text_field($_COOKIE['paksa_cr_session'] ?? '');

        $existing = null;
        if ($user_id) {
            $existing = Paksa_DB::get_active_cart_by_user($user_id);
        } elseif ($session_id) {
            $existing = Paksa_DB::get_active_cart_by_session($session_id);
        }

        if (!$existing) {
            // Create one on the fly
            $items = [];
            foreach (WC()->cart->get_cart() as $item) {
                $product = $item['data'] ?? null;
                if (!$product || !($product instanceof WC_Product)) continue;
                $items[] = [
                    'product_id'   => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? 0,
                    'name'         => $product->get_name(),
                    'quantity'     => $item['quantity'],
                    'price'        => (float) $product->get_price(),
                    'line_total'   => (float) ($item['quantity'] * $product->get_price()),
                    'image'        => wp_get_attachment_url($product->get_image_id()) ?: wc_placeholder_img_src(),
                    'url'          => get_permalink($item['product_id']),
                ];
            }

            $cart_id = Paksa_DB::insert_cart([
                'session_id'      => $session_id,
                'user_id'         => $user_id,
                'cart_data'       => maybe_serialize($items),
                'cart_total'      => (float) WC()->cart->get_total('edit'),
                'cart_items_count'=> WC()->cart->get_cart_contents_count(),
                'status'          => 'active',
            ]);
            $existing = Paksa_DB::get_cart($cart_id);
        }

        if (!$existing) {
            wp_send_json_error('Could not create share link');
            return;
        }

        $share_url = add_query_arg([
            'paksa_recover' => $existing->recovery_token,
            'preview'       => '1',
        ], home_url('/'));

        $store_name = get_bloginfo('name');
        $wa_text = rawurlencode(sprintf(
            __("Check out what I'm buying at %s! Total: %s\n%s", 'paksa-cart-recovery'),
            $store_name,
            strip_tags(wc_price($existing->cart_total)),
            $share_url
        ));

        wp_send_json_success([
            'share_url'    => $share_url,
            'whatsapp_url' => 'https://wa.me/?text=' . $wa_text,
            'token'        => $existing->recovery_token,
        ]);
    }

    /**
     * Shortcode [paksa_share_cart] to place share button anywhere.
     */
    public function share_button_shortcode(): string {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return '';
        }
        ob_start();
        $this->add_share_button_to_cart();
        return ob_get_clean();
    }
}
