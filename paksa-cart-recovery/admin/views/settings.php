<?php defined('ABSPATH') || exit; ?>

<div class="paksa-cr-settings">
    <form method="post">
        <?php wp_nonce_field('paksa_cr_settings'); ?>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('🛒 Cart Tracking', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="abandon_timeout"><?php esc_html_e('Abandon Timeout (minutes)', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="abandon_timeout" name="abandon_timeout" value="<?php echo esc_attr(get_option('paksa_cr_abandon_timeout', 30)); ?>" min="5" max="1440" class="small-text">
                        <p class="description"><?php esc_html_e('Cart is marked abandoned after this many minutes of inactivity. Recommended: 15-60 minutes.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="retention_days"><?php esc_html_e('Data Retention (days)', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="retention_days" name="retention_days" value="<?php echo esc_attr(get_option('paksa_cr_retention_days', 90)); ?>" min="7" max="365" class="small-text">
                        <p class="description"><?php esc_html_e('Automatically delete abandoned/expired carts older than this period.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="token_expiry_days"><?php esc_html_e('Recovery Link Expiry (days)', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="token_expiry_days" name="token_expiry_days" value="<?php echo esc_attr(get_option('paksa_cr_token_expiry_days', 7)); ?>" min="1" max="30" class="small-text">
                        <p class="description"><?php esc_html_e('Recovery links expire after this many days.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('💬 WhatsApp Recovery', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable WhatsApp Button', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="whatsapp_enabled" value="yes" <?php checked(get_option('paksa_cr_whatsapp_enabled', 'yes'), 'yes'); ?>> <?php esc_html_e('Show WhatsApp button on abandoned carts list', 'paksa-cart-recovery'); ?></label>
                        <p class="description"><?php esc_html_e('Opens WhatsApp with pre-filled recovery message for quick customer contact.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="whatsapp_message"><?php esc_html_e('WhatsApp Message Template', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <textarea id="whatsapp_message" name="whatsapp_message" rows="4" class="large-text"><?php echo esc_textarea(get_option('paksa_cr_whatsapp_message', '')); ?></textarea>
                        <p class="description"><?php esc_html_e('Placeholders: {customer_name}, {cart_total}, {recovery_link}, {store_name}', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('📧 Email Recovery', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Email Recovery', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="email_enabled" value="yes" <?php checked(get_option('paksa_cr_email_enabled', 'no'), 'yes'); ?>> <?php esc_html_e('Send automated recovery emails (only for customers who provided email)', 'paksa-cart-recovery'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Email Schedule', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="email_1h" value="yes" <?php checked(get_option('paksa_cr_email_1h', 'yes'), 'yes'); ?>> <?php esc_html_e('Send reminder after 1 hour', 'paksa-cart-recovery'); ?></label><br>
                        <label><input type="checkbox" name="email_24h" value="yes" <?php checked(get_option('paksa_cr_email_24h', 'yes'), 'yes'); ?>> <?php esc_html_e('Send follow-up after 24 hours', 'paksa-cart-recovery'); ?></label><br>
                        <label><input type="checkbox" name="email_72h" value="yes" <?php checked(get_option('paksa_cr_email_72h', 'yes'), 'yes'); ?>> <?php esc_html_e('Send final reminder after 72 hours', 'paksa-cart-recovery'); ?></label>
                    </td>
                </tr>
            </table>
        </div>

        <p><input type="submit" name="paksa_cr_save_settings" class="button button-primary" value="<?php esc_attr_e('💾 Save Settings', 'paksa-cart-recovery'); ?>"></p>
    </form>
</div>
