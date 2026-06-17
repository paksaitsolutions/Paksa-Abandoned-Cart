<?php defined('ABSPATH') || exit; ?>

<div class="paksa-cr-emails">
    <p><?php esc_html_e('Customize recovery email templates. Available placeholders: {customer_name}, {recovery_link}, {cart_total}, {store_name}, {store_url}, {products_list}, {items_count}', 'paksa-cart-recovery'); ?></p>

    <form method="post">
        <?php wp_nonce_field('paksa_cr_emails'); ?>

        <?php foreach (['1h' => '1 Hour', '24h' => '24 Hours', '72h' => '72 Hours'] as $key => $label):
            $template = Paksa_Email::get_template($key);
        ?>
        <div class="paksa-cr-card">
            <h3><?php printf(esc_html__('Email After %s', 'paksa-cart-recovery'), $label); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="subject_<?php echo esc_attr($key); ?>"><?php esc_html_e('Subject', 'paksa-cart-recovery'); ?></label></th>
                    <td><input type="text" id="subject_<?php echo esc_attr($key); ?>" name="subject_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($template['subject']); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label for="body_<?php echo esc_attr($key); ?>"><?php esc_html_e('Body (HTML)', 'paksa-cart-recovery'); ?></label></th>
                    <td><textarea id="body_<?php echo esc_attr($key); ?>" name="body_<?php echo esc_attr($key); ?>" rows="8" class="large-text"><?php echo esc_textarea($template['body']); ?></textarea></td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>

        <p><input type="submit" name="paksa_cr_save_emails" class="button button-primary" value="<?php esc_attr_e('Save Templates', 'paksa-cart-recovery'); ?>"></p>
    </form>
</div>
