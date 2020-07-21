<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function removeSlashes($str)
{
    while (strpos($str, "\'") !== false) {
        $str = str_replace("\'", "'", $str);
    }

    return $str;
}
/*
 * Made I.T.
 *
 * @package Made I.T.
 * @since 1.0.0
 */
?>
<div class="wrap">
    <h1><?php
        if ($form['id'] == 0) {
            echo esc_html(__('Add New Contact Form', 'forms-by-made-it'));
        } else {
            echo esc_html(__('Edit Contact Form', 'forms-by-made-it'));
            echo ' <a href="'.esc_url(menu_page_url('madeit_form', false)).'" class="add-new-h2">'.esc_html(__('Add New', 'forms-by-made-it')).'</a>';
        }
    ?></h1>
    <form method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>" id="madeit-forms-admin-form-element">
        <input type="hidden" name="add_new" value="Y">
        <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html(__('Enter title here', 'forms-by-made-it')); ?></label>
                            <input type="text" name="title" size="30" value="<?php echo $form['title']; ?>" id="title" spellcheck="true" autocomplete="off">
                        </div><!-- #titlewrap -->
                        <?php if ($form['id'] != 0) {
        ?>
                            <div class="inside">
                                <p class="description">
                                    <label for="madeit-forms-shortcode"><?php echo esc_html(__('Copy this shortcode and paste it into your post, page, or text widget content:', 'forms-by-made-it')); ?></label>
                                    <span class="shortcode wp-ui-highlight"><input type="text" id="madeit-forms-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="<?php echo esc_attr('[form id="'.$form['id'].'"]'); ?>" /></span>
                                </p>
                            </div>
                        <?php
    } ?>
                    </div><!-- #titlediv -->
                </div><!-- #post-body-content -->

                <div id="postbox-container-1" class="postbox-container">
                    <div id="submitdiv" class="postbox">
                        <h3><?php echo esc_html(__('Status', 'forms-by-made-it')); ?></h3>
                        <div class="inside">
                            <div class="submitbox" id="submitpost">
                                <div id="minor-publishing-actions">
                                    <?php if ($form['id'] != 0) {
        ?>
                                        <input type="submit" name="madeit_forms-copy" class="copy button" value="<?php echo esc_attr(__('Duplicate', 'forms-by-made-it')); ?>" <?php echo "onclick=\"this.form._wpnonce.value = ''; this.form.action.value = 'copy'; return true;\""; ?> />
                                   <?php
    } ?>
                                </div><!-- #minor-publishing-actions -->

                                <div id="misc-publishing-actions">
                                    <?php
                                    $errors = $this->checkFormActions($form['id']);
                                    if ($errors > 0) {
                                        $message = sprintf(_n('%s configuration error found', '%s configuration errors found', $errors, 'forms-by-made-it'), $errors);
                                        $link = sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url('https://www.madeit.be/producten/wordpress/forms-plugin/#configuration-validator'), __("What's this?", 'forms-by-made-it'), '');
                                        echo sprintf('<div class="misc-pub-section warning">%1$s<br />%2$s</div>', $message, $link);
                                    }
                                    ?>
                                </div><!-- #misc-publishing-actions -->

                                <div id="major-publishing-actions">
                                    <?php if ($form['id'] != 0) {
                                        ?>
                                        <div id="delete-action">
                                            <input type="submit" name="madeit-forms-delete" class="delete submitdelete" value="<?php echo esc_attr(__('Delete', 'forms-by-made-it')); ?>" <?php echo "onclick=\"if (confirm('".esc_js(__("You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'forms-by-made-it'))."')) {this.form._wpnonce.value = ''; this.form.action.value = 'delete'; return true;} return false;\""; ?> />
                                        </div><!-- #delete-action -->
                                    <?php
                                    } ?>

                                    <div id="publishing-action">
                                        <span class="spinner"></span>
                                        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('madeit-form-save-contact-form'); ?>">
                                        <input type="submit" class="button-primary" name="madeit-forms-save" value="<?php echo esc_attr(__('Save', 'forms-by-made-it')); ?>" />
                                    </div>
                                    <div class="clear"></div>
                                </div><!-- #major-publishing-actions -->
                            </div><!-- #submitpost -->
                        </div>
                    </div><!-- #submitdiv -->


                    <div id="informationdiv" class="postbox">
                        <h3><?php echo esc_html(__('Information', 'forms-by-made-it')); ?></h3>
                        <div class="inside">
                            <ul>
                                <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url('https://www.madeit.be/forms-plugin/docs/'), __('Docs', 'forms-by-made-it'), ''); ?></li>
                                <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url('https://www.madeit.be/forms-plugin/faq'), __('F.A.Q.', 'forms-by-made-it'), ''); ?></li>
                                <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url('https://www.madeit.be/forms-plugin/'), __('Support', 'forms-by-made-it'), ''); ?></li>
                            </ul>
                        </div>
                    </div><!-- #informationdiv -->
                </div><!-- #postbox-container-1 -->

                <div id="postbox-container-2" class="postbox-container">
                    <div id="madeit-tab">
                        <ul id="madeit-tab-tabs">
                            <li id="form-panels-tab"><a href="#form-panel"><?php echo esc_html(__('Form', 'forms-by-made-it')); ?></a></li>
                            <li id="actions-panels-tab"><a href="#actions-panel"><?php echo esc_html(__('Actions', 'forms-by-made-it')); ?></a></li>
                            <li id="messages-panels-tab"><a href="#messages-panel"><?php echo esc_html(__('Messages', 'forms-by-made-it')); ?></a></li>
                        </ul>
                        <div class="madeit-tab-panel" id="form-panel">
                            <h2><?php echo esc_html(__('Form', 'forms-by-made-it')); ?></h2>
                            <span id="tag-generator-list">
                                <?php
                                foreach ($this->tags as $id => $panel) {
                                    echo sprintf(
                                        '<a href="#TB_inline?width=600&height=550&inlineId=%1$s" class="thickbox button" title="%2$s">%3$s</a>',
                                        esc_attr($panel['content'].'-'.$id),
                                        esc_attr(sprintf(__('Form-tag Generator: %s', 'forms-by-made-it'), $panel['title'])),
                                        esc_html($panel['title'])
                                    );
                                }
                                ?>
                            </span>
                            <?php
                            $formValue = $form['form'];
                            $formValue = str_replace('\"', '"', $formValue);
                            ?>
                            
                            <textarea id="madeit-forms-form" name="form" cols="100" rows="24" class="large-text code"><?php echo esc_textarea($formValue); ?></textarea>
                        </div>
                        <div class="madeit-tab-panel" id="actions-panel">
                           <h2><?php echo esc_html(__('Actions', 'forms-by-made-it')); ?></h2>
                            <fieldset>
                                <legend><?php echo esc_html(__('In the following fields, you can use these name-tags:', 'forms-by-made-it')); ?><br /><span class="name-tags"></span></legend>
                                <?php
                                if (isset($form['actions']) && count($form['actions']) > 0) {
                                    foreach ($form['actions'] as $actID => $actionInfo) {
                                        ?>
                                        <section id="action-panel-<?php echo $actID; ?>" data-id="<?php echo $actID; ?>" data-section-id="action-panel-" class="action-section">
                                            <input type="hidden" name="action_panel_<?php echo $actID; ?>" value="<?php echo $actID; ?>" data-name="action_panel_">
                                            <span style="float:right; margin: 5px;"><a href="javascript:void(0);" class="delete-section" style="text-decoration:none;"><span class="dashicons dashicons-no-alt"></span></a></span>
                                            <h3><?php echo esc_html(__('Action', 'forms-by-made-it')); ?>
                                                <?php if (isset($actionInfo['key'])) {
                                            ?>
                                                    <input type="hidden" name="action_key_<?php echo $actID; ?>" value="<?php echo esc_attr($actionInfo['key']); ?>" />
                                                    <?php
                                                    echo ' - ('.__('Key', 'forms-by-made-it').':'.$actionInfo['key'].')';
                                        } ?></h3>
                                            <table class="form-table">
                                                <tbody>
                                                    <tr data-name="action_type_">
                                                        <th scope="row">
                                                            <label for="action_type_<?php echo $actID; ?>"><?php echo esc_html(__('Type', 'forms-by-made-it')); ?></label>
                                                        </th>
                                                        <td>
                                                            <select name="action_type_<?php echo $actID; ?>" class="large-text code" style="width:100%">
                                                                <?php
                                                                foreach ($this->actions as $id => $action) {
                                                                    ?>
                                                                    <option value="<?php echo esc_html($id); ?>" <?php echo ($actionInfo['_id'] == $id) ? 'SELECTED' : ''; ?>><?php echo esc_html($action['title']); ?></option>
                                                                <?php
                                                                } ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    foreach ($this->actions as $id => $action) {
                                                        foreach ($action['action_fields'] as $name => $info) {
                                                            $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value']; ?>
                                                            <tr class="ACTION_<?php echo esc_html($id); ?>" data-name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_">
                                                                <th scope="row">
                                                                    <label for="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>"><?php echo esc_html($info['label']); ?></label>
                                                                </th>
                                                                <td>
                                                                    <?php
                                                                    if ($info['type'] == 'text') {
                                                                        ?>
                                                                        <input type="text" name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="large-text code" size="70" value="<?php echo esc_attr($inputValue); ?>" />
                                                                        <?php
                                                                    } elseif ($info['type'] == 'select') {
                                                                        ?>
                                                                        <select name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="large-text code" size="70" width="100%">
                                                                            <?php foreach ($info['options'] as $key => $val) {
                                                                            ?>
                                                                                <option value="<?php echo esc_html($key); ?>" <?php if ($key == $inputValue) {
                                                                                echo 'SELECTED';
                                                                            } ?>><?php echo esc_html($val); ?></option>
                                                                            <?php
                                                                        } ?> 
                                                                        </select>
                                                                        <?php
                                                                    } elseif ($info['type'] == 'textarea') {
                                                                        $value = stripcslashes($inputValue); ?>
                                                                        <textarea name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="large-text code" style="min-height: <?php echo isset($info['options']['min-height']) ? $info['options']['min-height'] : '50px'; ?>;"><?php echo $value; ?></textarea>
                                                                        <?php
                                                                    } elseif ($info['type'] == 'checkbox') {
                                                                        ?>
                                                                        <input type="checkbox" name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="" value="checked" <?php if ($inputValue == 'checked') {
                                                                            echo 'CHECKED';
                                                                        } ?>>
                                                                        <?php
                                                                    } ?>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </section>
                                        <?php
                                    }
                                }
                                ?>
                            </fieldset>
                            <span style="float:right; margin: 5px"><a href="javascript:void(0);" class="add-section" style="text-decoration:none;"><span class="dashicons dashicons-plus"></span></a></span>
                            <div class="clear"></div>
                        </div>
                        <div class="madeit-tab-panel" id="messages-panel">
                           <h2><?php echo esc_html(__('Messages', '')); ?></h2>
                            <fieldset>
                                <legend><?php echo esc_html(__('In the following fields, you can use these name-tags:', 'forms-by-made-it')); ?><br /><span class="name-tags"></span></legend>
                                <?php
                                foreach ($this->messages as $arr) {
                                    $value = isset($form['messages'][$arr['field']]) ? $form['messages'][$arr['field']] : $arr['value']; ?>
                                    <p class="description">
                                        <label for="<?php echo $arr['field']; ?>"><?php echo esc_html($arr['description']); ?><br />
                                            <input type="text" id="messages_<?php echo $arr['field']; ?>" name="messages_<?php echo $arr['field']; ?>" class="large-text" size="70" value="<?php echo esc_attr(removeSlashes($value)); ?>" />
                                        </label>
                                    </p>
                                    <?php
                                }
                                ?>
                            </fieldset>
                        </div>
                    </div><!-- #madeit-tab -->
                </div><!-- #postbox-container-2 -->
            </div><!-- #post-body -->
            <br class="clear" />
        </div><!-- #poststuff -->
    </form>
</div><!-- .wrap -->

<div id="empty-actions-section" style="display: none;">
    <section id="action-panel-" data-id="0" data-section-id="action-panel-" class="action-section">
        <input type="hidden" name="action_panel_" value="" data-name="action_panel_">
        <span style="float:right; margin: 5px;"><a href="javascript:void(0);" class="delete-section" style="text-decoration:none;"><span class="dashicons dashicons-no-alt"></span></a></span>
        <h3><?php echo esc_html(__('Action', 'forms-by-made-it')); ?></h3>
        <table class="form-table">
            <tbody>
                <tr data-name="action_type_">
                    <th scope="row">
                        <label for="action_type_"><?php echo esc_html(__('Type', 'forms-by-made-it')); ?></label>
                    </th>
                    <td>
                        <select name="action_type_" class="large-text code" style="width:100%">
                            <?php
                            foreach ($this->actions as $id => $action) {
                                ?>
                                <option value="<?php echo esc_html($id); ?>"><?php echo esc_html($action['title']); ?></option>
                            <?php
                            } ?>
                        </select>
                    </td>
                </tr>
                <?php
                foreach ($this->actions as $id => $action) {
                    foreach ($action['action_fields'] as $name => $info) {
                        $inputValue = isset($info['value']) ? $info['value'] : '';
                        $actID = $id; ?>
                        <tr class="ACTION_<?php echo esc_html($id); ?>" data-name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_">
                            <th scope="row">
                                <label for="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_"><?php echo esc_html($info['label']); ?></label>
                            </th>
                            <td>
                                <?php
                                if ($info['type'] == 'text') {
                                    ?>
                                    <input type="text" name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="large-text code" size="70" value="<?php echo esc_attr($inputValue); ?>" />
                                    <?php
                                } elseif ($info['type'] == 'select') {
                                    ?>
                                    <select name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="large-text code" size="70" width="100%">
                                        <?php foreach ($info['options'] as $key => $val) {
                                        ?>
                                            <option value="<?php echo esc_html($key); ?>" <?php if ($key == $inputValue) {
                                            echo 'SELECTED';
                                        } ?>><?php echo esc_html($val); ?></option>
                                        <?php
                                    } ?> 
                                    </select>
                                    <?php
                                } elseif ($info['type'] == 'textarea') {
                                    $value = stripcslashes($inputValue); ?>
                                    <textarea name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="large-text code" style="min-height: <?php echo isset($info['options']['min-height']) ? $info['options']['min-height'] : '50px'; ?>;"><?php echo $value; ?></textarea>
                                    <?php
                                } elseif ($info['type'] == 'checkbox') {
                                    ?>
                                    <input type="checkbox" name="action_<?php echo esc_html($id); ?>_<?php echo esc_html($name); ?>_<?php echo $actID; ?>" class="" value="checked" <?php if ($inputValue == 'checked') {
                                        echo 'CHECKED';
                                    } ?>>
                                    <?php
                                } ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </section>
</div>
<?php
add_thickbox();
foreach ($this->tags as $id => $panel) {
    $callback = $panel['form'];
    if (is_callable($callback)) {
        echo sprintf('<div id="%s" class="hidden">', esc_attr($panel['content'].'-'.$id));
        echo sprintf('<form action="" class="tag-generator-panel" data-id="%s">', $id);
        call_user_func($callback, '', array_merge($panel, ['id' => $id]));
        echo '</form></div>';
    }
}
