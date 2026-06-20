<?php
defined('ABSPATH') || exit;

/**
 * IP Geolocation — resolves IP addresses to city/region/country.
 * Uses ip-api.com (free, no API key, 45 req/min).
 */
class Paksa_Geolocation {

    public function __construct() {
        add_action('paksa_cr_check_abandoned', [$this, 'resolve_pending_locations']);
    }

    /**
     * Resolve location for an IP address.
     * Returns formatted string like "Lahore, Punjab, Pakistan" or empty on failure.
     */
    public static function lookup(string $ip): string {
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '';
        }

        // Check transient cache first
        $cache_key = 'paksa_cr_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,city,regionName,country", [
            'timeout' => 5,
            'headers' => ['User-Agent' => 'Paksa-Cart-Recovery/' . PAKSA_CR_VERSION],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data) || ($data['status'] ?? '') !== 'success') {
            // Cache failure briefly to avoid hammering
            set_transient($cache_key, '', 30 * MINUTE_IN_SECONDS);
            return '';
        }

        $parts = array_filter([
            $data['city'] ?? '',
            $data['regionName'] ?? '',
            $data['country'] ?? '',
        ]);
        $location = implode(', ', $parts);

        // Cache for 7 days (IPs rarely change location)
        set_transient($cache_key, $location, 7 * DAY_IN_SECONDS);

        return $location;
    }

    /**
     * Cron job: resolve locations for carts that have IP but no location yet.
     * Processes in batches to respect API rate limits.
     */
    public function resolve_pending_locations(): void {
        global $wpdb;
        $table = Paksa_DB::table();

        // Get carts with IP but no location (max 20 per cron run to stay under rate limit)
        $carts = $wpdb->get_results(
            "SELECT id, ip_address FROM {$table}
             WHERE ip_address != '' AND location = ''
             ORDER BY id DESC LIMIT 20"
        );

        if (empty($carts)) return;

        foreach ($carts as $cart) {
            $location = self::lookup($cart->ip_address);
            if ($location !== '') {
                $wpdb->update($table, ['location' => $location], ['id' => $cart->id]);
            } else {
                // Mark as attempted so we don't retry endlessly
                $wpdb->update($table, ['location' => '—'], ['id' => $cart->id]);
            }
            // Small delay to respect rate limits
            usleep(100000); // 100ms
        }
    }
}
