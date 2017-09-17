<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Made I.T. 
 * 
 * @package Made I.T.
 * @since 1.0.0
 */
?>
<div class="wrap">
    <h1><?php echo esc_html(__('Settings', 'forms-by-made-it')); ?></h1>
    <form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" id="madeit-security-admin-form-element">
        <?php if($success) {
            ?>
            <div class="updated"><p><strong><?php echo __('The settings are successfully saved.', 'forms-by-made-it'); ?></strong></p></div>
            <?php
        }
        if(!empty($error)) {
            ?>
            <div class="error"><p><strong><?php echo __($error, 'forms-by-made-it'); ?></strong></p></div>
            <?php
        }
        ?>
        <input type="hidden" name="save_settings" value="Y">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="postbox-container-1" class="postbox-container">
                    <div id="informationdiv" class="postbox">
                        <h3><?php echo esc_html(__( 'Information', 'forms-by-made-it')); ?></h3>
                        <div class="inside">
                            <ul>
                                <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url("https://www.madeit.be/producten/wordpress/forms-plugin/#docs"), __('Docs', 'forms-by-made-it'), ""); ?></li>
                                <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url("https://www.madeit.be/producten/wordpress/forms-plugin/#faq"), __('F.A.Q.', 'forms-by-made-it'), ""); ?></li>
                                <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url("https://www.madeit.be/producten/wordpress/forms-plugin/#support"), __('Support', 'forms-by-made-it'), ""); ?></li>
                            </ul>
                        </div>
                    </div><!-- #informationdiv -->
                </div><!-- #postbox-container-1 -->

                <div id="postbox-container-2" class="postbox-container">
                    <div id="madeit-tab">
                        <ul id="madeit-tab-tabs">
                            <li id="general-settings-tab"><a href="#general-settings"><?php echo esc_html(__('General settings', 'forms-by-made-it')); ?></a></li>
                        </ul>
                        <div class="madeit-tab-panel settings" id="general-settings">
                            <section class="section">
                                <h3><?php echo esc_html(__('Security', 'forms-by-made-it')); ?></h3>
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for=""><?php echo esc_html(__('Enable reCaptcha', 'forms-by-made-it')); ?></label>
                                            </th>
                                            <td>
                                                <input type="checkbox" name="madeit_forms_reCaptcha" class="" value="1" <?php if($this->defaultSettings['reCaptcha']['enabled']) echo "CHECKED"; ?> />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for=""><?php echo esc_html(__('Google invisble reCaptcha key', 'madeit_security')); ?></label>
                                            </th>
                                            <td>
                                                <input type="text" name="madeit_forms_reCaptcha_key" class="large-text code" size="70" value="<?php echo $this->defaultSettings['reCaptcha']['key']; ?>" />
                                                <p>
                                                    <a href="https://www.google.com/recaptcha/admin" target="_blank"><?php echo esc_html(__('Create your key here.', 'forms-by-made-it')) ?></a>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for=""><?php echo esc_html(__('Google invisble reCaptcha secret key', 'madeit_security')); ?></label>
                                            </th>
                                            <td>
                                                <input type="text" name="madeit_forms_reCaptcha_secret" class="large-text code" size="70" value="<?php echo $this->defaultSettings['reCaptcha']['secret']; ?>" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </section>
                        </div>
                    </div><!-- #madeit-tab -->
                    <p class="submit">
                        <?php
                        $nonce = wp_create_nonce('madeit_forms_settings');
                        ?>
                        <input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>" />
                        <input type="submit" class="button-primary" value="<?php echo esc_html(__('Save', 'forms-by-made-it')); ?>" />
                    </p>
                </div><!-- #postbox-container-2 -->
            </div><!-- #post-body -->
            <br class="clear" />
        </div><!-- #poststuff -->
    </form>
</div><!-- .wrap -->
<?php
/*add_thickbox();*/