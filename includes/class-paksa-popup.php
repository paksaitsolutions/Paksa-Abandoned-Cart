<?php
defined('ABSPATH') || exit;

/**
 * Exit Intent Popup — captures phone number when customer tries to leave with items in cart.
 */
class Paksa_Popup {

    public function __construct() {
        add_action('wp_footer', [$this, 'render_popup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        if (get_option('paksa_cr_popup_enabled', 'no') !== 'yes') return;
        if (is_checkout()) return; // Don't show on checkout — already capturing there
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) return;

        wp_enqueue_style('paksa-cr-popup', PAKSA_CR_URL . 'assets/css/popup.css', [], PAKSA_CR_VERSION);
        wp_enqueue_script('paksa-cr-popup', PAKSA_CR_URL . 'assets/js/popup.js', ['jquery'], PAKSA_CR_VERSION, true);
        wp_localize_script('paksa-cr-popup', 'paksa_cr_popup', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('paksa_cr_nonce'),
            'delay'    => (int) get_option('paksa_cr_popup_delay', 0),
            'trigger'  => get_option('paksa_cr_popup_trigger', 'exit'),
        ]);
    }

    public function render_popup(): void {
        if (get_option('paksa_cr_popup_enabled', 'no') !== 'yes') return;
        if (is_checkout()) return;
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) return;

        // Don't show if phone already captured for this session
        if (!empty($_COOKIE['paksa_cr_popup_shown'])) return;

        $heading = get_option('paksa_cr_popup_heading', __('Wait! Don\'t leave yet!', 'paksa-cart-recovery'));
        $text    = get_option('paksa_cr_popup_text', __('Enter your phone number and we\'ll save your cart for you.', 'paksa-cart-recovery'));
        $button  = get_option('paksa_cr_popup_button', __('Save My Cart', 'paksa-cart-recovery'));
        $coupon_enabled = get_option('paksa_cr_coupon_enabled', 'no') === 'yes';
        $discount_type   = get_option('paksa_cr_coupon_type', 'percent');
        $discount_amount = (float) get_option('paksa_cr_coupon_amount', 10);
        $discount_text   = $discount_type === 'percent' ? $discount_amount . '% OFF' : strip_tags(wc_price($discount_amount)) . ' OFF';
        ?>
        <div id="paksa-cr-popup" class="paksa-cr-popup-overlay" style="display:none;">
            <div class="paksa-cr-popup-box">
                <button type="button" class="paksa-cr-popup-close">&times;</button>
                <?php if ($coupon_enabled): ?>
                    <div class="paksa-cr-popup-badge"><?php echo esc_html($discount_text); ?></div>
                <?php endif; ?>
                <h3><?php echo esc_html($heading); ?></h3>
                <p><?php echo esc_html($text); ?></p>
                <form id="paksa-cr-popup-form">
                    <input type="tel" id="paksa-cr-popup-phone" placeholder="<?php esc_attr_e('03XX XXXXXXX', 'paksa-cart-recovery'); ?>" required>
                    <input type="text" id="paksa-cr-popup-name" placeholder="<?php esc_attr_e('Your Name (optional)', 'paksa-cart-recovery'); ?>">
                    <button type="submit" class="paksa-cr-popup-btn"><?php echo esc_html($button); ?></button>
                </form>
                <p class="paksa-cr-popup-success" style="display:none;">✅ <?php esc_html_e('Cart saved! We\'ll remind you.', 'paksa-cart-recovery'); ?></p>
                <p class="paksa-cr-popup-privacy"><?php esc_html_e('We respect your privacy. No spam.', 'paksa-cart-recovery'); ?></p>
            </div>
        </div>
        <?php
    }
}
