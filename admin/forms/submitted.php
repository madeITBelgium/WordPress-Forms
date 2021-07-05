<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Made I.T.
 *
 * @package Made I.T.
 * @since 1.0.0
 */
?>
<!-- . begining of wrap -->
<div class="wrap">
    <?php echo screen_icon('options-general'); ?>
    <!-- beginning of the settings meta box -->
    <di class="post-box-container">
        <div class="metabox-holder">
            <div class="meta-box-sortables ui-sortable">
                <div id="settings" class="postbox">
                    <div class="handlediv" title="<?php echo __('Click to toggle', 'forms-by-made-it') ?>"><br /></div>
                    <!-- settings box title -->
                    <h3 class="hndle">
                        <span style="vertical-align: top;"><?php echo esc_textarea($form['title']) ?> - <?php echo $f['create_time']; ?></span>
                    </h3>
                    <div class="inside">
                        <table class="form-table">
                            <tbody>
                                <?php
                                foreach (json_decode($f['data'], true) as $k => $v) {
                                    ?>
                                    <tr>
                                        <th scope="row">
                                            <label><strong><?php echo esc_textarea($k); ?></strong></label>
                                        </th>
                                        <td>
                                            <?php echo nl2br(esc_html($v)); ?>
                                        </td>
                                    </tr>
                                    <?php
                                } ?>
                                <tr>
                                    <th scope="row">
                                        <label><strong><?php echo __('IP', 'forms-by-made-it'); ?></strong></label>
                                    </th>
                                    <td>
                                        <?php echo esc_html($f['ip']); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><strong><?php echo __('User agent', 'forms-by-made-it'); ?></strong></label>
                                    </th>
                                    <td>
                                        <?php echo esc_html($f['user_agent']); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><strong><?php echo __('Date', 'forms-by-made-it'); ?></strong></label>
                                    </th>
                                    <td>
                                        <?php echo esc_html($f['create_time']); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div><!-- .inside -->
                </div><!-- #settings -->
            </div><!-- .meta-box-sortables ui-sortable -->
        </div><!-- .metabox-holder -->
    </div><!-- end of the settings meta box -->
</div><!-- end of wrap -->