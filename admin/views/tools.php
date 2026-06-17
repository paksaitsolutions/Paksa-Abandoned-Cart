<?php defined('ABSPATH') || exit; ?>

<div class="paksa-cr-tools">
    <div class="paksa-cr-row">
        <div class="paksa-cr-col">
            <div class="paksa-cr-card">
                <h2><?php esc_html_e('🧹 Database Cleanup', 'paksa-cart-recovery'); ?></h2>
                <p><?php printf(esc_html__('Delete abandoned and expired carts older than %d days (configured in Settings).', 'paksa-cart-recovery'), (int) get_option('paksa_cr_retention_days', 90)); ?></p>
                <button type="button" class="button button-secondary" id="paksa-cr-cleanup"><?php esc_html_e('Run Cleanup Now', 'paksa-cart-recovery'); ?></button>
                <span id="paksa-cr-cleanup-result" class="paksa-cr-tool-result"></span>
            </div>

            <div class="paksa-cr-card">
                <h2><?php esc_html_e('📥 Export Data', 'paksa-cart-recovery'); ?></h2>
                <p><?php esc_html_e('Export all abandoned cart data as a CSV file for external analysis.', 'paksa-cart-recovery'); ?></p>
                <button type="button" class="button button-secondary" id="paksa-cr-export-all"><?php esc_html_e('Export All Carts (CSV)', 'paksa-cart-recovery'); ?></button>
            </div>
        </div>

        <div class="paksa-cr-col">
            <div class="paksa-cr-card">
                <h2><?php esc_html_e('ℹ️ Plugin Information', 'paksa-cart-recovery'); ?></h2>
                <table class="form-table paksa-cr-info-table">
                    <tr><th><?php esc_html_e('Plugin Version', 'paksa-cart-recovery'); ?></th><td><code><?php echo esc_html(PAKSA_CR_VERSION); ?></code></td></tr>
                    <tr><th><?php esc_html_e('DB Version', 'paksa-cart-recovery'); ?></th><td><code><?php echo esc_html(get_option('paksa_cr_db_version', '—')); ?></code></td></tr>
                    <tr><th><?php esc_html_e('DB Table', 'paksa-cart-recovery'); ?></th><td><code><?php echo esc_html(Paksa_DB::table()); ?></code></td></tr>
                    <tr><th><?php esc_html_e('Next Cron Run', 'paksa-cart-recovery'); ?></th><td><?php
                        $next = wp_next_scheduled('paksa_cr_check_abandoned');
                        echo $next ? esc_html(date_i18n('Y-m-d H:i:s', $next) . ' (' . human_time_diff(time(), $next) . ' from now)') : '<span style="color:#d63638;">' . esc_html__('Not scheduled!', 'paksa-cart-recovery') . '</span>';
                    ?></td></tr>
                    <tr><th><?php esc_html_e('Abandon Timeout', 'paksa-cart-recovery'); ?></th><td><?php echo esc_html(get_option('paksa_cr_abandon_timeout', 30) . ' minutes'); ?></td></tr>
                    <tr><th><?php esc_html_e('Data Retention', 'paksa-cart-recovery'); ?></th><td><?php echo esc_html(get_option('paksa_cr_retention_days', 90) . ' days'); ?></td></tr>
                    <tr><th><?php esc_html_e('Email Recovery', 'paksa-cart-recovery'); ?></th><td><?php echo get_option('paksa_cr_email_enabled', 'no') === 'yes' ? '✅ Enabled' : '❌ Disabled'; ?></td></tr>
                    <tr><th><?php esc_html_e('WhatsApp', 'paksa-cart-recovery'); ?></th><td><?php echo get_option('paksa_cr_whatsapp_enabled', 'yes') === 'yes' ? '✅ Enabled' : '❌ Disabled'; ?></td></tr>
                    <tr><th><?php esc_html_e('PHP Version', 'paksa-cart-recovery'); ?></th><td><code><?php echo esc_html(PHP_VERSION); ?></code></td></tr>
                    <tr><th><?php esc_html_e('WooCommerce', 'paksa-cart-recovery'); ?></th><td><code><?php echo esc_html(defined('WC_VERSION') ? WC_VERSION : '—'); ?></code></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="paksa-cr-card">
        <h2><?php esc_html_e('👨‍💻 About', 'paksa-cart-recovery'); ?></h2>
        <p><?php esc_html_e('Paksa Cart Recovery is developed by Paksa IT Solutions for Pakistani eCommerce stores.', 'paksa-cart-recovery'); ?></p>
        <p>
            🌐 <a href="https://paksa.com.pk" target="_blank">paksa.com.pk</a> &nbsp;&bull;&nbsp;
            📧 <a href="mailto:info@paksa.com.pk">info@paksa.com.pk</a>
        </p>
    </div>
</div>
