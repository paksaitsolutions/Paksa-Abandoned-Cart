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
            <h2><?php esc_html_e('🎟️ Recovery Coupon', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Coupon', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="coupon_enabled" value="yes" <?php checked(get_option('paksa_cr_coupon_enabled', 'no'), 'yes'); ?>> <?php esc_html_e('Auto-generate discount coupon with recovery messages', 'paksa-cart-recovery'); ?></label>
                        <p class="description"><?php esc_html_e('A unique coupon is included in WhatsApp messages and emails to incentivize purchase.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="coupon_type"><?php esc_html_e('Discount Type', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <select id="coupon_type" name="coupon_type">
                            <option value="percent" <?php selected(get_option('paksa_cr_coupon_type', 'percent'), 'percent'); ?>><?php esc_html_e('Percentage (%)', 'paksa-cart-recovery'); ?></option>
                            <option value="fixed_cart" <?php selected(get_option('paksa_cr_coupon_type', 'percent'), 'fixed_cart'); ?>><?php esc_html_e('Fixed Amount', 'paksa-cart-recovery'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="coupon_amount"><?php esc_html_e('Discount Amount', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="coupon_amount" name="coupon_amount" value="<?php echo esc_attr(get_option('paksa_cr_coupon_amount', 10)); ?>" min="1" max="90" step="1" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="coupon_expiry"><?php esc_html_e('Coupon Expiry (hours)', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="coupon_expiry" name="coupon_expiry" value="<?php echo esc_attr(get_option('paksa_cr_coupon_expiry', 48)); ?>" min="1" max="720" class="small-text">
                        <p class="description"><?php esc_html_e('Coupon expires after this many hours. Creates urgency.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="coupon_min_cart"><?php esc_html_e('Minimum Cart Value', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="coupon_min_cart" name="coupon_min_cart" value="<?php echo esc_attr(get_option('paksa_cr_coupon_min_cart', 0)); ?>" min="0" step="1" class="small-text">
                        <p class="description"><?php esc_html_e('Set to 0 for no minimum. Coupons only generated for carts above this value.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('🚪 Exit Intent Popup', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Popup', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="popup_enabled" value="yes" <?php checked(get_option('paksa_cr_popup_enabled', 'no'), 'yes'); ?>> <?php esc_html_e('Show popup to capture phone number when customer tries to leave', 'paksa-cart-recovery'); ?></label>
                        <p class="description"><?php esc_html_e('Captures customer phone number early — before they reach checkout.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="popup_trigger"><?php esc_html_e('Trigger Method', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <select id="popup_trigger" name="popup_trigger">
                            <option value="exit" <?php selected(get_option('paksa_cr_popup_trigger', 'exit'), 'exit'); ?>><?php esc_html_e('Exit Intent (mouse leaves window)', 'paksa-cart-recovery'); ?></option>
                            <option value="timer" <?php selected(get_option('paksa_cr_popup_trigger', 'exit'), 'timer'); ?>><?php esc_html_e('Time Delay', 'paksa-cart-recovery'); ?></option>
                            <option value="both" <?php selected(get_option('paksa_cr_popup_trigger', 'exit'), 'both'); ?>><?php esc_html_e('Both (whichever happens first)', 'paksa-cart-recovery'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="popup_delay"><?php esc_html_e('Timer Delay (seconds)', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="popup_delay" name="popup_delay" value="<?php echo esc_attr(get_option('paksa_cr_popup_delay', 30)); ?>" min="5" max="300" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="popup_heading"><?php esc_html_e('Popup Heading', 'paksa-cart-recovery'); ?></label></th>
                    <td><input type="text" id="popup_heading" name="popup_heading" value="<?php echo esc_attr(get_option('paksa_cr_popup_heading', "Wait! Don't leave yet!")); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label for="popup_text"><?php esc_html_e('Popup Text', 'paksa-cart-recovery'); ?></label></th>
                    <td><input type="text" id="popup_text" name="popup_text" value="<?php echo esc_attr(get_option('paksa_cr_popup_text', "Enter your phone number and we'll save your cart for you.")); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label for="popup_button"><?php esc_html_e('Button Text', 'paksa-cart-recovery'); ?></label></th>
                    <td><input type="text" id="popup_button" name="popup_button" value="<?php echo esc_attr(get_option('paksa_cr_popup_button', 'Save My Cart')); ?>" class="regular-text"></td>
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
                        <p class="description"><?php esc_html_e('Placeholders: {customer_name}, {cart_total}, {recovery_link}, {store_name}, {coupon_code}, {coupon_text}', 'paksa-cart-recovery'); ?></p>
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

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('🚨 Admin Notifications', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Admin Alerts', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="admin_notify" value="yes" <?php checked(get_option('paksa_cr_admin_notify', 'no'), 'yes'); ?>> <?php esc_html_e('Email me when a high-value cart is abandoned', 'paksa-cart-recovery'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="admin_notify_threshold"><?php esc_html_e('Minimum Cart Value', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="number" id="admin_notify_threshold" name="admin_notify_threshold" value="<?php echo esc_attr(get_option('paksa_cr_admin_notify_threshold', 5000)); ?>" min="0" class="small-text">
                        <p class="description"><?php esc_html_e('Only notify for carts above this value.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="admin_notify_email"><?php esc_html_e('Notification Email', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="email" id="admin_notify_email" name="admin_notify_email" value="<?php echo esc_attr(get_option('paksa_cr_admin_notify_email', get_option('admin_email'))); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('🔗 Webhooks (Zapier/n8n)', 'paksa-cart-recovery'); ?></h2>
            <p class="description"><?php esc_html_e('Enter webhook URLs to receive cart events as JSON POST requests.', 'paksa-cart-recovery'); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="webhook_abandoned"><?php esc_html_e('Cart Abandoned URL', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="url" id="webhook_abandoned" name="webhook_abandoned" value="<?php echo esc_attr(get_option('paksa_cr_webhook_abandoned', '')); ?>" class="large-text" placeholder="https://hooks.zapier.com/...">
                    </td>
                </tr>
                <tr>
                    <th><label for="webhook_recovered"><?php esc_html_e('Cart Recovered URL', 'paksa-cart-recovery'); ?></label></th>
                    <td>
                        <input type="url" id="webhook_recovered" name="webhook_recovered" value="<?php echo esc_attr(get_option('paksa_cr_webhook_recovered', '')); ?>" class="large-text" placeholder="https://hooks.zapier.com/...">
                    </td>
                </tr>
            </table>
        </div>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('🔔 Browser Push Notifications', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Push Notifications', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="push_enabled" value="yes" <?php checked(get_option('paksa_cr_push_enabled', 'no'), 'yes'); ?>> <?php esc_html_e('Ask visitors for notification permission and send recovery alerts', 'paksa-cart-recovery'); ?></label>
                        <p class="description"><?php esc_html_e('Sends browser notification when cart is abandoned. Works without email or phone.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="paksa-cr-card">
            <h2><?php esc_html_e('📱 Cart Sharing', 'paksa-cart-recovery'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Cart Sharing', 'paksa-cart-recovery'); ?></th>
                    <td>
                        <label><input type="checkbox" name="share_enabled" value="yes" <?php checked(get_option('paksa_cr_share_enabled', 'yes'), 'yes'); ?>> <?php esc_html_e('Show "Share Cart" button on cart page', 'paksa-cart-recovery'); ?></label>
                        <p class="description"><?php esc_html_e('Customers can share their cart via WhatsApp or link. Useful when someone else is paying.', 'paksa-cart-recovery'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p><input type="submit" name="paksa_cr_save_settings" class="button button-primary" value="<?php esc_attr_e('💾 Save Settings', 'paksa-cart-recovery'); ?>"></p>
    </form>
</div>
