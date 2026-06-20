<?php
/**
 * Plugin Name: Paksa Cart Recovery
 * Plugin URI: https://github.com/paksaitsolutions/Paksa-Abandoned-Cart
 * Description: Phone-number-based abandoned cart recovery for WooCommerce. Built for Pakistani eCommerce markets.
 * Version: 1.4.0
 * Author: Paksa IT Solutions
 * Author URI: https://paksa.com.pk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paksa-cart-recovery
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 */

defined('ABSPATH') || exit;

define('PAKSA_CR_VERSION', '1.4.0');
define('PAKSA_CR_FILE', __FILE__);
define('PAKSA_CR_PATH', plugin_dir_path(__FILE__));
define('PAKSA_CR_URL', plugin_dir_url(__FILE__));
define('PAKSA_CR_TABLE', 'paksa_abandoned_carts');

// Activation/deactivation MUST be registered at file load time
register_activation_hook(__FILE__, 'paksa_cr_activate');
register_deactivation_hook(__FILE__, 'paksa_cr_deactivate');

function paksa_cr_activate(): void {
    // Register custom cron interval before scheduling
    add_filter('cron_schedules', function($schedules) {
        $schedules['every_fifteen_minutes'] = [
            'interval' => 900,
            'display'  => 'Every 15 Minutes',
        ];
        return $schedules;
    });

    require_once PAKSA_CR_PATH . 'includes/class-paksa-db.php';
    Paksa_DB::activate();
}

function paksa_cr_deactivate(): void {
    wp_clear_scheduled_hook('paksa_cr_check_abandoned');
}

// HPOS compatibility - declared early
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PAKSA_CR_FILE, true);
    }
});

/**
 * Main plugin class — initialized after WooCommerce loads.
 */
final class Paksa_Cart_Recovery {

    private static ?self $instance = null;

    public static function instance(): self {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes(): void {
        require_once PAKSA_CR_PATH . 'includes/class-paksa-db.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-tracker.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-recovery.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-cron.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-email.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-coupon.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-popup.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-geolocation.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-notifications.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-webhooks.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-push.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-landing-page.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-cart-share.php';
        require_once PAKSA_CR_PATH . 'includes/class-paksa-updater.php';

        if (is_admin()) {
            require_once PAKSA_CR_PATH . 'admin/class-paksa-admin.php';
        }
    }

    private function init_hooks(): void {
        add_action('init', [$this, 'load_textdomain']);
        add_action('template_redirect', [Paksa_Recovery::class, 'handle_recovery_link']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_scripts']);

        new Paksa_Tracker();
        new Paksa_Cron();
        new Paksa_Coupon();
        new Paksa_Popup();
        new Paksa_Geolocation();
        new Paksa_Notifications();
        new Paksa_Webhooks();
        new Paksa_Push();
        new Paksa_Landing_Page();
        new Paksa_Cart_Share();
        new Paksa_Updater();

        if (is_admin()) {
            new Paksa_Admin();
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('paksa-cart-recovery', false, dirname(plugin_basename(PAKSA_CR_FILE)) . '/languages');
    }

    public function frontend_scripts(): void {
        if (!function_exists('is_checkout') || (!is_checkout() && !is_cart())) return;

        wp_enqueue_script('paksa-cr-checkout', PAKSA_CR_URL . 'assets/js/checkout.js', ['jquery'], PAKSA_CR_VERSION, true);
        wp_localize_script('paksa-cr-checkout', 'paksa_cr', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('paksa_cr_nonce'),
            'is_checkout' => is_checkout() ? 1 : 0,
        ]);
    }
}

/**
 * Boot the plugin only when WooCommerce is active.
 */
add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            printf(
                '<div class="error"><p><strong>%s</strong> %s</p></div>',
                'Paksa Cart Recovery',
                esc_html__('requires WooCommerce to be installed and active.', 'paksa-cart-recovery')
            );
        });
        return;
    }
    Paksa_Cart_Recovery::instance();
});
