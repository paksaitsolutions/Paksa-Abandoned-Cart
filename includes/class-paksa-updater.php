<?php
defined('ABSPATH') || exit;

/**
 * GitHub-based auto-updater for Paksa Cart Recovery.
 * Checks GitHub releases for new versions and enables one-click updates from WP admin.
 */
class Paksa_Updater {

    private string $slug = 'paksa-cart-recovery';
    private string $plugin_file;
    private string $github_repo = 'paksaitsolutions/Paksa-Abandoned-Cart';
    private string $current_version;
    private ?object $github_response = null;

    public function __construct() {
        $this->plugin_file     = plugin_basename(PAKSA_CR_FILE);
        $this->current_version = PAKSA_CR_VERSION;

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    /**
     * Fetch latest release data from GitHub API.
     */
    private function get_github_release(): ?object {
        if ($this->github_response !== null) {
            return $this->github_response;
        }

        $transient_key = 'paksa_cr_github_release';
        $cached = get_transient($transient_key);

        if ($cached !== false) {
            $this->github_response = $cached;
            return $this->github_response;
        }

        $url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'Paksa-Cart-Recovery/' . $this->current_version,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $this->github_response = (object) [];
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        if (!$body || empty($body->tag_name)) {
            $this->github_response = (object) [];
            return null;
        }

        $this->github_response = $body;
        set_transient($transient_key, $body, 6 * HOUR_IN_SECONDS);

        return $this->github_response;
    }

    /**
     * Get the download URL for the zip asset from the release.
     */
    private function get_download_url(): string {
        $release = $this->get_github_release();
        if (!$release || empty($release->assets)) {
            return '';
        }

        // Look for paksa-cart-recovery.zip in assets
        foreach ($release->assets as $asset) {
            if (strpos($asset->name, 'paksa-cart-recovery') !== false && strpos($asset->name, '.zip') !== false) {
                return $asset->browser_download_url;
            }
        }

        // Fallback: use zipball
        return $release->zipball_url ?? '';
    }

    /**
     * Get remote version number (strip 'v' prefix).
     */
    private function get_remote_version(): string {
        $release = $this->get_github_release();
        if (!$release || empty($release->tag_name)) {
            return '';
        }
        return ltrim($release->tag_name, 'v');
    }

    /**
     * Hook into WordPress update check.
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();
        if (empty($remote_version)) {
            return $transient;
        }

        if (version_compare($this->current_version, $remote_version, '<')) {
            $download_url = $this->get_download_url();
            if (empty($download_url)) {
                return $transient;
            }

            $transient->response[$this->plugin_file] = (object) [
                'slug'        => $this->slug,
                'plugin'      => $this->plugin_file,
                'new_version' => $remote_version,
                'url'         => "https://github.com/{$this->github_repo}",
                'package'     => $download_url,
                'icons'       => [],
                'banners'     => [],
                'tested'      => '',
                'requires_php'=> '8.0',
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin info for the "View Details" popup.
     */
    public function plugin_info($result, string $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $release = $this->get_github_release();
        if (!$release || empty($release->tag_name)) {
            return $result;
        }

        return (object) [
            'name'            => 'Paksa Cart Recovery',
            'slug'            => $this->slug,
            'version'         => $this->get_remote_version(),
            'author'          => '<a href="https://paksa.com.pk">Paksa IT Solutions</a>',
            'homepage'        => "https://github.com/{$this->github_repo}",
            'requires'        => '6.0',
            'requires_php'    => '8.0',
            'tested'          => get_bloginfo('version'),
            'downloaded'      => 0,
            'last_updated'    => $release->published_at ?? '',
            'sections'        => [
                'description'  => 'Phone-number-based abandoned cart recovery for WooCommerce. Built for Pakistani eCommerce markets.',
                'changelog'    => nl2br(esc_html($release->body ?? 'See GitHub releases for changelog.')),
            ],
            'download_link'   => $this->get_download_url(),
        ];
    }

    /**
     * After install, rename the extracted folder to match plugin slug.
     */
    public function after_install($response, $hook_extra, $result) {
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $result;
        }

        global $wp_filesystem;

        $plugin_dir = WP_PLUGIN_DIR . '/' . $this->slug;
        $wp_filesystem->move($result['destination'], $plugin_dir);
        $result['destination'] = $plugin_dir;

        // Reactivate if it was active
        if (is_plugin_active($this->plugin_file)) {
            activate_plugin($this->plugin_file);
        }

        return $result;
    }
}
