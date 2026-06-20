<?php
/**
 * Paksa Cart Recovery - Uninstall
 * 
 * This file runs when the plugin is deleted from WordPress admin.
 * It removes ALL plugin data from the database.
 * WordPress automatically deletes the plugin folder/files.
 */

// Exit if not called by WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Drop custom database table
$table = $wpdb->prefix . 'paksa_abandoned_carts';
$wpdb->query("DROP TABLE IF EXISTS {$table}");

// 2. Delete all plugin options
$options = [
    'paksa_cr_db_version',
    'paksa_cr_abandon_timeout',
    'paksa_cr_retention_days',
    'paksa_cr_token_expiry_days',
    'paksa_cr_email_enabled',
    'paksa_cr_email_1h',
    'paksa_cr_email_24h',
    'paksa_cr_email_72h',
    'paksa_cr_email_template_1h',
    'paksa_cr_email_template_24h',
    'paksa_cr_email_template_72h',
    'paksa_cr_whatsapp_enabled',
    'paksa_cr_whatsapp_message',
    'paksa_cr_coupon_enabled',
    'paksa_cr_coupon_type',
    'paksa_cr_coupon_amount',
    'paksa_cr_coupon_expiry',
    'paksa_cr_coupon_min_cart',
    'paksa_cr_popup_enabled',
    'paksa_cr_popup_trigger',
    'paksa_cr_popup_delay',
    'paksa_cr_popup_heading',
    'paksa_cr_popup_text',
    'paksa_cr_popup_button',
    'paksa_cr_admin_notify',
    'paksa_cr_admin_notify_threshold',
    'paksa_cr_admin_notify_email',
    'paksa_cr_notified_ids',
    'paksa_cr_webhook_abandoned',
    'paksa_cr_webhook_recovered',
];

foreach ($options as $option) {
    delete_option($option);
}

// 3. Clear all scheduled cron events
wp_clear_scheduled_hook('paksa_cr_check_abandoned');

// 4. Delete any transients created by the plugin
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%paksa_cr%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_paksa%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_paksa%'");

// 5. Clean user meta if any
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '%paksa_cr%'");

// 6. Clean WooCommerce sessions related to plugin
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%paksa_cr_session%'");
