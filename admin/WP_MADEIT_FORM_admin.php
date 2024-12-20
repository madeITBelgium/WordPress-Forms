<?php
class WP_MADEIT_FORM_admin
{
    private $cycles;
    private $tags = [];
    private $actions = [];
    private $messages = [];
    private $settings;
    private $defaultSettings;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->defaultSettings = $this->settings->loadDefaultSettings();
    }

    public function initMenu()
    {
        add_submenu_page('edit.php?post_type=ma_forms', __('Made I.T. Forms - Settings', 'forms-by-made-it'), __('Settings', 'forms-by-made-it'), 'manage_options', 'madeit_forms_settings', [$this, 'settings']);
    }

    public function initStyle()
    {
        wp_register_style('madeit-form-admin-style', MADEIT_FORM_URL.'admin/css/style.css', [], null);
        wp_register_style('madeit-tabs', MADEIT_FORM_URL.'admin/css/tabs.css', [], null);
        wp_enqueue_style('madeit-tabs');
        wp_enqueue_style('madeit-form-admin-style');

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('madeit-form-script', MADEIT_FORM_URL.'admin/js/script.js', ['jquery'], 2, true);
        wp_enqueue_script('madeit-tabs', MADEIT_FORM_URL.'admin/js/tabs.js', ['jquery'], 2, true);
    }

    public function addAction($id, $value)
    {
        $this->actions[$id] = $value;
        if (count($value['message_fields']) > 0) {
            $this->messages = array_merge($this->messages, $value['message_fields']);
        }
    }

    public function addModule($id, $value)
    {
        $this->tags[$id] = $value;
        if (count($value['message_fields']) > 0) {
            $this->messages = array_merge($this->messages, $value['message_fields']);
        }
    }

    public function initAdmin()
    {
        if (isset($_GET['post_type']) && isset($_GET['action']) && $_GET['post_type'] === 'ma_form_inputs' && $_GET['action'] === 'export') {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'export_forms')) {
                wp_die('Security check');
            }
            $data = get_posts([
                'post_type'   => 'ma_form_inputs',
                'numberposts' => -1,
                'meta_query'  => [
                    [
                        'key'   => 'form_id',
                        'value' => $_GET['id'],
                    ],
                ],
            ]);

            $form = get_post($_GET['id']);
            if ($form->post_type !== 'ma_forms') {
                exit();
            }

            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=export-madeit-forms-'.date('Y-m-d-H-i-s').'.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            if (count($data) === 0) {
                exit();
            }

            $row = [
                'id'   => '',
                'form' => '',
            ];

            foreach (json_decode($this->removeSlashes(get_post_meta($data[0]->ID, 'data', true)), true) as $k => $v) {
                $row[$k] = $v;
            }

            $row['ip'] = '';
            $row['user_agent'] = '';
            $row['date'] = '';

            unset($row['g-recaptcha-response']);
            $columns = array_keys($row);

            // output the column headings
            fputcsv($output, $columns, ";");

            // fetch the data
            foreach ($data as $d) {
                $row = [
                    'id'   => $d->ID,
                    'form' => $form->post_title,
                ];

                foreach (json_decode($this->removeSlashes(get_post_meta($d->ID, 'data', true)), true) as $k => $v) {
                    if(is_array($v)) {
                        $v = implode(", ", $v);
                    }
                    $row[$k] = $v;
                }

                $row['ip'] = get_post_meta($d->ID, 'ip', true);
                $row['user_agent'] = get_post_meta($d->ID, 'user_agent', true);
                $row['date'] = $d->post_date;

                unset($row['g-recaptcha-response']);
                fputcsv($output, $row, ";");
            }
            exit();
        } elseif (isset($_GET['post_type']) && isset($_GET['action']) && $_GET['post_type'] === 'ma_form_inputs' && $_GET['action'] === 'mark_as_read_forms') {
            if (!wp_verify_nonce($_GET['forms_wpnonce'], 'mark_as_read_forms')) {
                wp_die('Security check 1');
            }

            $p = get_posts([
                'post_type'  => 'ma_form_inputs',
                'meta_query' => [
                    [
                        'meta_key'   => 'read',
                        'meta_value' => 0,
                    ],
                ],
            ]);

            foreach ($p as $po) {
                update_post_meta($po->ID, 'read', 1);
            }
        }
    }

    public function settings()
    {
        $success = false;
        $error = '';
        if (isset($_POST['save_settings'])) {
            $success = $this->save_settings();
            if ($success !== true) {
                $error = $success;
                $success = false;
            }
        }
        include_once MADEIT_FORM_ADMIN.'/forms/settings.php';
    }

    private function save_settings()
    {
        $success = false;
        $nonce = $_POST['_wpnonce'];
        if (!wp_verify_nonce($nonce, 'madeit_forms_settings')) {
            // This nonce is not valid.
            wp_die('Security check');
        } else {
            $this->settings->checkCheckbox('madeit_forms_reCaptcha');
            $this->settings->checkTextbox('madeit_forms_reCaptcha_key');
            $this->settings->checkTextbox('madeit_forms_reCaptcha_secret');
            $this->settings->checkTextbox('madeit_forms_reCaptcha_version');
            $this->settings->checkTextbox('madeit_forms_reCaptcha_minScore');

            if (get_option('madeit_forms_reCaptcha_key', null) == null || get_option('madeit_forms_reCaptcha_secret', null) == null) {
                update_option('madeit_forms_reCaptcha', false);
            }
            $success = true;
        }
        $this->defaultSettings = $this->settings->loadDefaultSettings();

        return $success;
    }

    public function getTags($form)
    {
        $tags = [];
        foreach (explode('[', $form) as $v) {
            $v = trim($v);
            if (strlen($v) > 0) {
                $posName = strpos($v, 'name="');
                if ($posName !== false) {
                    $v = substr($v, $posName + 6);
                    $v = substr($v, 0, strpos($v, '"'));
                    $tags[] = $v;
                }
            }
        }

        return $tags;
    }

    public function checkFormActions($id)
    {
        $res = 0;
        $errors = [];

        $formValue = get_post_meta($id, 'form', true);
        $formValue = str_replace('\"', '"', $formValue);

        $tags = $this->getTags($formValue);
        $t = [];
        foreach ($tags as $a) {
            $t[$a] = 'a';
        }

        //execute actions
        $formActions = json_decode(str_replace("\'", "'", get_post_meta($id, 'actions', true)), true);
        if (!empty($formActions) && count($formActions) > 0) {
            foreach ($formActions as $actID => $actionInfo) {
                $action = $this->actions[$actionInfo['_id']];

                $data = [];
                foreach ($action['action_fields'] as $name => $info) {
                    $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value'];
                    $data[$name] = $this->changeInputTag($t, $inputValue);
                }

                foreach ($data as $key => $val) {
                    $pos = strpos($val, '[');
                    if ($pos !== false) {
                        $posN = strpos($val, ']', $pos);
                        $space = strpos($val, ' ', $pos);
                        if ($posN !== false) {
                            $res++;
                            $errors[] = $key;
                        }
                    }
                }
            }
        }

        return $res;
    }

    private function changeInputTag($tags, $value)
    {
        foreach ($tags as $k => $v) {
            $value = str_replace('['.$k.']', $v, $value);
        }

        return $value;
    }

    public function init()
    {
        $this->messages = [
            ['field' => 'success',             'description' => __('Form successfully completed', 'forms-by-made-it'),                     'value' => __('Thank you for your message. It has been sent.', 'forms-by-made-it')],
            ['field' => 'failed',              'description' => __('Form failed to complete', 'forms-by-made-it'),                         'value' => __('There was an error trying to send your message. Please try again later.', 'forms-by-made-it')],
            ['field' => 'validation_error',    'description' => __('Validation errors occurred', 'forms-by-made-it'),                      'value' => __('One or more fields have an error. Please check and try again.', 'forms-by-made-it')],
            ['field' => 'spam',                'description' => __('Submission was referred to as spam', 'forms-by-made-it'),              'value' => __('There was an error trying to send your message. Please try again later.', 'forms-by-made-it')],
            ['field' => 'accept_terms',        'description' => __('There are terms that the sender must accept', 'forms-by-made-it'),     'value' => __('You must accept the terms and conditions before sending your message.', 'forms-by-made-it')],
            ['field' => 'invalid_required',    'description' => __('There is a field that the sender must fill in', 'forms-by-made-it'),   'value' => __('The field is required.', 'forms-by-made-it')],
            ['field' => 'invalid_too_long',    'description' => __('There is a field with input that is longer than the maximum allowed length', 'forms-by-made-it'), 'value' => __('The field is too long.', 'forms-by-made-it')],
            ['field' => 'invalid_too_short',   'description' => __('There is a field with input that is shorter than the minimum allowed length', 'forms-by-made-it'), 'value' => __('The field is too short.', 'forms-by-made-it')],
            //array('field' => '', 'description' => __("", 'forms-by-made-it'), 'value' => __('', 'forms-by-made-it')),
        ];

        $actions = apply_filters('madeit_forms_actions', []);
        foreach ($actions as $id => $value) {
            $this->addAction($id, $value);
        }

        $modules = apply_filters('madeit_forms_modules', []);
        foreach ($modules as $id => $value) {
            $this->addModule($id, $value);
        }
    }

    public function set_custom_edit_ma_forms_columns($columns)
    {
        $columns['short_code'] = __('Shortcode', 'forms-by-made-it');

        return $columns;
    }

    public function custom_ma_forms_column($column, $post_id)
    {
        if ($column === 'short_code') {
            $formId = get_post_meta($post_id, 'form_id', true);
            if (!empty($formId)) {
                echo '<code>[form id="'.$formId.'"]</code> of ';
            }
            echo '<code>[form id="'.$post_id.'"]</code>';
        }
    }

    public function set_custom_edit_ma_form_inputs_columns($columns)
    {
        $date = $columns['date'];
        unset($columns['date']);
        $columns['form'] = __('Form', 'forms-by-made-it');
        $columns['read'] = __('Read', 'forms-by-made-it');
        $columns['date'] = $date;

        $inputFields = min($this->getMaxInputFields(), 5);
        for ($i = 1; $i <= $inputFields; $i++) {
            /* translators: %s: Field number */
            $columns['input_'.$i - 1] = sprintf(__('Field %s', 'forms-by-made-it'), $i);
        }

        return $columns;
    }

    public function custom_ma_form_inputs_column($column, $post_id)
    {
        if ($column === 'form') {
            $formId = get_post_meta($post_id, 'form_id', true);
            echo get_post($formId)->post_title;
        } elseif ($column === 'read') {
            echo get_post_meta($post_id, 'read', true) == 1 ? __('Yes', 'forms-by-made-it') : __('No', 'forms-by-made-it');
        } elseif (strpos($column, 'input_') !== false) {
            $fieldNr = substr($column, strlen('input_'));
            $formId = get_post_meta($post_id, 'form_id', true);

            $fields = $this->getInputFieldsOfForm($formId);
            if (isset($fields[$fieldNr])) {
                $fieldName = $fields[$fieldNr];

                $data = json_decode($this->removeSlashes($this->dbToEnter(str_replace("\'", "'", get_post_meta($post_id, 'data', true)))), true);
                
                $v = $data[$fieldName] ?? '';
                
                if(is_array($v)) {
                    echo implode(", ", $v);
                } else {
                    echo $v;
                }
            } else {
                $data = json_decode($this->removeSlashes($this->dbToEnter(str_replace("\'", "'", get_post_meta($post_id, 'data', true)))), true);
                $fields = array_keys($data ?? []);
                
                if (isset($fields[$fieldNr])) {
                    $fieldName = $fields[$fieldNr];
                    $v = $data[$fieldName] ?? '';
                    if($fieldName === 'g-recaptcha-response') {
                        $v = '';
                    }
                    if(is_array($v)) {
                        echo implode(", ", $v);
                    } else {
                        echo $v;
                    }
                }
            }
        }
    }

    /*
     * Show shortcode
     */
    public function edit_form_after_title($post)
    {
        if ($post->post_type === 'ma_forms') {
            if (isset($post->ID) && $post->ID > 0) {
                $formId = get_post_meta($post->ID, 'form_id', true);
                $formId = empty($formId) ? $post->ID : $formId; ?>
                <div class="inside">
                    <p class="description">
                        <label for="madeit-forms-shortcode"><?php echo esc_html(__('Copy this shortcode and paste it into your post, page, or text widget content:', 'forms-by-made-it')); ?></label>
                        <span class="shortcode wp-ui-highlight"><input type="text" id="madeit-forms-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="<?php echo esc_attr('[form id="'.$formId.'"]'); ?>" /></span>
                    </p>
                </div>
                <?php
            }
        }
    }

    /*
     * Form editor
     */
    public function edit_form_advanced($post)
    {
        if ($post->post_type === 'ma_forms' && get_post_meta($post->ID, 'form_type', true) === 'html') {
            $formValue = '<p>Your name:</p>
[text name="your-name"]
<p>Your email:</p>
[email name="your-email"]
[submit value="Send"]';

            $actions = [];
            $messages = [];
            $form = get_post_meta($post->ID, 'form', true);
            if (!empty($form)) {
                $formValue = $form;
                $messages = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($post->ID, 'messages', true))), true);
                $actions = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($post->ID, 'actions', true))), true);
            }

            $formValue = str_replace('\"', '"', $formValue); ?>
            <input type="hidden" name="madeit_form_editor" value="yes">
            <input type="hidden" name="save_inputs" value="<?php echo get_post_meta($post->ID, 'save_inputs', true); ?>">

            <div id="madeit-tab">
                <ul id="madeit-tab-tabs">
                    <li id="form-panels-tab"><a href="#form-panel"><?php echo esc_html(__('Form', 'forms-by-made-it')); ?></a></li>
                    <li id="actions-panels-tab"><a href="#actions-panel"><?php echo esc_html(__('Actions', 'forms-by-made-it')); ?></a></li>
                    <li id="messages-panels-tab"><a href="#messages-panel"><?php echo esc_html(__('Messages', 'forms-by-made-it')); ?></a></li>
                </ul>
                <div class="madeit-tab-panel" id="form-panel">
                    <h2><?php echo esc_html(__('Form', 'forms-by-made-it')); ?></h2>
                    <div id="madeit-form-text">
                        <span id="tag-generator-list">
                            <?php
                            foreach ($this->tags as $id => $panel) {
                                /* translators: %s: Panel title */
                                echo sprintf(
                                    '<a href="#TB_inline?width=600&height=550&inlineId=%1$s" class="thickbox button" title="%2$s">%3$s</a>',
                                    esc_attr($panel['content'].'-'.$id),
                                    esc_attr(sprintf(__('Form-tag Generator: %s', 'forms-by-made-it'), $panel['title'])),
                                    esc_html($panel['title'])
                                );
                            } ?>
                        </span>
                        <textarea id="madeit-forms-form" name="form" cols="100" rows="24" class="large-text code"><?php echo esc_textarea($formValue); ?></textarea>
                    </div>
                </div>
                <div class="madeit-tab-panel" id="actions-panel">
                   <h2><?php echo esc_html(__('Actions', 'forms-by-made-it')); ?></h2>
                    <fieldset>
                        <legend><?php echo esc_html(__('In the following fields, you can use these name-tags:', 'forms-by-made-it')); ?><br /><span class="name-tags"></span></legend>
                        <?php
                        if (isset($actions) && count($actions) > 0) {
                            foreach ($actions as $actID => $actionInfo) {
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
                        } ?>
                    </fieldset>
                    <span style="float:right; margin: 5px"><a href="javascript:void(0);" class="add-section" style="text-decoration:none;"><span class="dashicons dashicons-plus"></span></a></span>
                    <div class="clear"></div>
                </div>
                <div class="madeit-tab-panel" id="messages-panel">
                   <h2><?php echo esc_html(__('Messages', 'forms-by-made-it')); ?></h2>
                    <fieldset>
                        <legend><?php echo esc_html(__('In the following fields, you can use these name-tags:', 'forms-by-made-it')); ?><br /><span class="name-tags"></span></legend>
                        <?php
                        foreach ($this->messages as $arr) {
                            $value = isset($messages[$arr['field']]) ? $messages[$arr['field']] : $arr['value']; ?>
                            <p class="description">
                                <label for="<?php echo $arr['field']; ?>"><?php echo esc_html($arr['description']); ?><br />
                                    <input type="text" id="messages_<?php echo $arr['field']; ?>" name="messages_<?php echo $arr['field']; ?>" class="large-text" size="70" value="<?php echo esc_attr($this->removeSlashes($value)); ?>" />
                                </label>
                            </p>
                            <?php
                        } ?>
                    </fieldset>
                </div>
            </div><!-- #madeit-tab -->
            <?php
        }
    }

    public function admin_footer()
    {
        if ('ma_forms' === get_current_screen()->id) {
            ?>
            <style>.interface-interface-skeleton__content { padding-left: 10px; padding-right: 10px; }</style>
            <?php
        } ?>
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
                        } ?>
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
    }

    /*
     * Sidebar
     */
    public function submitpost_box($post)
    {
        if ($post->post_type === 'ma_forms') {
            ?>
            <div id="informationdiv" class="postbox">
                <h3><?php echo esc_html(__('Information', 'forms-by-made-it')); ?></h3>
                <div class="inside">
                    <ul>
                        <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s" target="_blank">%2$s</a>', esc_url('https://www.madeit.be/forms-plugin/docs/'), __('Docs', 'forms-by-made-it'), ''); ?></li>
                        <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s" target="_blank">%2$s</a>', esc_url('https://www.madeit.be/forms-plugin/faq'), __('F.A.Q.', 'forms-by-made-it'), ''); ?></li>
                        <li><?php echo sprintf('<a href="%1$s"%3$s" title="%2$s" target="_blank">%2$s</a>', esc_url('https://www.madeit.be/forms-plugin/'), __('Support', 'forms-by-made-it'), ''); ?></li>
                    </ul>
                </div>
            </div><!-- #informationdiv -->

            <?php
            $errors = $this->checkFormActions($post->ID);
            if ($errors > 0) {
                /* translators: %s: Number of errors */
                $message = sprintf(_n('%s configuration error found', '%s configuration errors found', $errors, 'forms-by-made-it'), $errors);
                $link = sprintf('<a href="%1$s"%3$s" title="%2$s">%2$s</a>', esc_url('https://www.madeit.be/producten/wordpress/forms-plugin/#configuration-validator'), __("What's this?", 'forms-by-made-it'), '');
                echo sprintf('<div class="misc-pub-section warning">%1$s<br />%2$s</div>', $message, $link);
            }
        }
    }

    public function save_form($post_id, $post, $update)
    {
        global $_POST;

        if (isset($_POST['madeit_form_editor']) && $_POST['madeit_form_editor'] == 'yes') {
            update_post_meta($post_id, 'save_inputs', 1);
            update_post_meta($post_id, 'form', $_POST['form']);
            update_post_meta($post_id, 'form_type', 'html');
            /* TODO DELETE?
            $actions = [];
            $messages = [];
            //actions
            $countActions = 0;
            foreach ($_POST as $k => $v) {
                if (substr($k, 0, strlen('action_panel_')) == 'action_panel_' && is_numeric($v) && $v > $countActions) {
                    $countActions = $v;
                }
            }

            $j = 1;
            for ($i = 1; $i <= $countActions; $i++) {
                $id = $_POST['action_type_'.$i];
                if (isset($this->actions[$id])) {
                    $action = $this->actions[$id];
                    $actions[$j] = ['_id' => $id];
                    foreach ($action['action_fields'] as $name => $info) {
                        $actions[$j][$name] = isset($_POST['action_'.$id.'_'.$name.'_'.$i]) ? $_POST['action_'.$id.'_'.$name.'_'.$i] : '';
                    }
                    $actions[$j]['key'] = isset($_POST['action_key_'.$i]) ? $_POST['action_key_'.$i] : $this->generateKey();
                }
                $j++;
            }

            foreach ($_POST as $k => $v) {
                if (substr($k, 0, strlen('messages_')) == 'messages_') {
                    $messages[substr($k, strlen('messages_'))] = $v;
                }
            }

            update_post_meta($post_id, 'messages', $this->enterToDB(json_encode($messages)));
            update_post_meta($post_id, 'actions', $this->enterToDB(json_encode($actions)));
            */
        }
    }

    public function save_meta($post_id, $post, $update)
    {
        // Do not save the data if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if ($post->post_type != 'ma_forms') {
            return $post_id;
        }

        update_post_meta($post_id, 'save_inputs', true);

        if (isset($_POST['ma_forms_save_meta_type'])) {
            $actions = [];
            $messages = [];
            //actions
            $countActions = 0;
            foreach ($_POST as $k => $v) {
                if (substr($k, 0, strlen('action_panel_')) == 'action_panel_' && is_numeric($v) && $v > $countActions) {
                    $countActions = $v;
                }
            }

            $j = 1;
            for ($i = 1; $i <= $countActions; $i++) {
                $id = $_POST['action_type_'.$i];
                if (isset($this->actions[$id])) {
                    $action = $this->actions[$id];
                    $actions[$j] = ['_id' => $id];
                    foreach ($action['action_fields'] as $name => $info) {
                        $actions[$j][$name] = isset($_POST['action_'.$id.'_'.$name.'_'.$i]) ? $_POST['action_'.$id.'_'.$name.'_'.$i] : '';
                    }
                    $actions[$j]['key'] = isset($_POST['action_key_'.$i]) ? $_POST['action_key_'.$i] : $this->generateKey();
                }
                $j++;
            }

            foreach ($_POST as $k => $v) {
                if (substr($k, 0, strlen('messages_')) == 'messages_') {
                    $messages[substr($k, strlen('messages_'))] = $v;
                }
            }

            if(isset($_POST['settings_max_submits'])) {
                update_post_meta($post_id, 'max_submits', $_POST['settings_max_submits']);
            } else {
                delete_post_meta($post_id, 'max_submits');
            }

            update_post_meta($post_id, 'actions', $this->enterToDB(json_encode($actions)));
            update_post_meta($post_id, 'messages', $this->enterToDB(json_encode($messages)));
        }
    }

    public function removeSlashes($str)
    {
        while (strpos($str, "\'") !== false) {
            $str = str_replace("\'", "'", $str);
        }

        return $str;
    }

    public function add_meta_boxes()
    {
        global $post;
        
        add_meta_box('ma_form_inputs_data', __('Submitted form data', 'forms-by-made-it'), [$this, 'ma_form_inputs_data'], 'ma_form_inputs', 'normal', 'high');

        if ($post && get_post_meta($post->ID, 'form_type', true) !== 'html') {
            add_meta_box('ma_forms_actions', __('Actions', 'forms-by-made-it'), [$this, 'ma_forms_actions'], 'ma_forms', 'normal', 'high');
            add_meta_box('ma_forms_messages', __('Messages', 'forms-by-made-it'), [$this, 'ma_forms_messages'], 'ma_forms', 'normal', 'high');
            add_meta_box('ma_forms_settings', __('Settings', 'forms-by-made-it'), [$this, 'ma_forms_settings'], 'ma_forms', 'normal', 'high');
        }
    }

    public function ma_forms_actions($post)
    {
        $actions = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($post->ID, 'actions', true))), true); ?>
        <div id="actions-panel">
            <input type="hidden" name="ma_forms_save_meta_type" value="actions">
            <fieldset>
                <legend><?php echo esc_html(__('In the following fields, you can use these name-tags:', 'forms-by-made-it')); ?><br /><span class="name-tags"></span></legend>
                <?php
                if (isset($actions) && count($actions) > 0) {
                    foreach ($actions as $actID => $actionInfo) {
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
                } ?>
            </fieldset>
            <span style="float:right; margin: 5px"><a href="javascript:void(0);" class="add-section" style="text-decoration:none;"><span class="dashicons dashicons-plus"></span></a></span>
            <div class="clear"></div>
        </div>
        <?php
    }

    public function ma_forms_messages($post)
    {
        $messages = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($post->ID, 'messages', true))), true); ?>
        <fieldset>
            <input type="hidden" name="ma_forms_save_meta_type" value="messages">
            <legend><?php echo esc_html(__('In the following fields, you can use these name-tags:', 'forms-by-made-it')); ?><br /><span class="name-tags"></span></legend>
            <?php
            foreach ($this->messages as $arr) {
                $value = isset($messages[$arr['field']]) ? $messages[$arr['field']] : $arr['value']; ?>
                <p class="description">
                    <label for="<?php echo $arr['field']; ?>"><?php echo esc_html($arr['description']); ?><br />
                        <input type="text" id="messages_<?php echo $arr['field']; ?>" name="messages_<?php echo $arr['field']; ?>" class="large-text" size="70" value="<?php echo esc_attr($this->removeSlashes($value)); ?>" />
                    </label>
                </p>
                <?php
            } ?>
        </fieldset>
        <?php
    }


    public function ma_forms_settings($post)
    {
        $maxAantalInzendingen = get_post_meta($post->ID, 'max_submits', true);
        ?>
        <fieldset>
            <input type="hidden" name="ma_forms_save_meta_type" value="settings">
            <p class="description">
                <label for="settings_max_submits">Maximaal aantal inzendingen:<br />
                    <input type="numeric" id="settings_max_submits" name="settings_max_submits" class="large-text" size="70" value="<?php echo $maxAantalInzendingen; ?>" />
                </label>
            </p>
        </fieldset>
        <?php
    }

    public function ma_form_inputs_data($post)
    {
        if (in_array(get_post_meta($post->ID, 'read', true), [0, '', null])) {
            update_post_meta($post->ID, 'read', 1);
        }
        $data = json_decode($this->removeSlashes($this->dbToEnter(get_post_meta($post->ID, 'data', true))), true); ?>
        <table class="form-table">
            <tbody>
                <?php
                foreach ($data as $k => $v) {
                    ?>
                    <tr>
                        <th scope="row">
                            <label><strong><?php echo esc_textarea($k); ?></strong></label>
                        </th>
                        <td>
                            <?php 
                            if(is_array($v)) {
                                echo esc_html(implode(", ", $v));
                            }
                            else {
                                echo nl2br(esc_html($v));
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                } ?>
                <tr>
                    <th scope="row">
                        <label><strong><?php echo __('IP', 'forms-by-made-it'); ?></strong></label>
                    </th>
                    <td>
                        <?php echo esc_html(get_post_meta($post->ID, 'ip', true)); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><strong><?php echo __('User agent', 'forms-by-made-it'); ?></strong></label>
                    </th>
                    <td>
                        <?php echo esc_html(get_post_meta($post->ID, 'user_agent', true)); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><strong><?php echo __('Date', 'forms-by-made-it'); ?></strong></label>
                    </th>
                    <td>
                        <?php echo esc_html($post->post_date); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <button class="button" role="button" id="btnResend">
            Resend mail
        </button>
        <script>
            document.getElementById('btnResend').addEventListener('click', function(e) {
                e.preventDefault();
                var data = {
                    'action': 'ma_forms_resend_mail',
                    'id': <?php echo $post->ID; ?>
                };
                jQuery.post(ajaxurl, data, function(response) {
                    alert(response);
                });
            });
        </script>
        <?php
    }

    public function resendMail() {
        $id = $_POST['id'];

        //TODO!!
        
        $data = json_decode($this->dbToEnter(get_post_meta($id, 'data', true)), true);
        $formId = get_post_meta($id, 'form_id', true);
        
        $data['message'] = "Beste,

        Onderstaande gegevens zijn ingevoerd via de website
        
        Van: [field-1]
        E-mailadres: [field-2]
        Telefoon: [field-3]
        Adres: [field-4], [field-5] [field-6]
        Organisatie: [field-7]
        Gelegenheid: [field-9]
        Aantal personen: [field-12]
        Datum: [field-10]
        Bericht: [field-11]";

        $data['to'] = "";
        $data['subject'] = "Contact via website ";
        $data['header'] = "Reply-to: " . $data['field-2'] . "\r\n";

        foreach($data as $key => $v) {
            $data['message'] = str_replace("[" . $key . "]", $v, $data['message']);
        }

        $mail = new WP_MADEIT_FORM_Email();
        if($mail->callback($data, [], null, $formId, $id, null)) {
            echo "Gelukt!";
        } else {
            echo "Mislukt!";
        }

        wp_die();
    }

    public function admin_menu()
    {
        global $menu;
        global $submenu;

        $new = '';
        $count = 0;
        $count = count(get_posts([
            'post_type'   => 'ma_form_inputs',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => 'read',
                    'value' => 0,
                ],
            ],
        ]));

        if ($count > 0) {
            $new = "<span class='update-plugins' title='".__('Unread form submits', 'forms-by-made-it')."'><span class='update-count'>".number_format_i18n($count).'</span></span>';
        }

        foreach ($menu as $k => $m) {
            if ($m[0] == __('Forms', 'forms-by-made-it')) {
                $menu[$k][0] .= $new;
            }
        }
        $submenu['edit.php?post_type=ma_forms'][11][0] .= $new;
    }

    public function form_inputs_export_button()
    {
        $screen = get_current_screen();
        if ('edit-ma_form_inputs' === $screen->id) {
            add_action('in_admin_footer', function () {
                $forms = get_posts([
                    'post_type'   => 'ma_forms',
                    'numberposts' => -1,
                ]); ?>
                <div style="display:flex;">
                    <div>
                        <form method="get" action="/wp-admin/edit.php">
                            <?php wp_nonce_field('export_forms'); ?>
                            <input type="hidden" name="post_type" value="ma_form_inputs">
                            <input type="hidden" name="action" value="export">
                            <select name="id">
                                <?php foreach ($forms as $form) { ?>
                                <option value="<?php echo $form->ID; ?>"><?php echo esc_textarea($form->post_title); ?></option>
                                <?php } ?>
                            </select>
                            <input type="submit" value="Exporteer" class="button">
                        </form>
                    </div>
                    <div style="margin-left: 15px; padding-left: 15px; border-left: 1px solid gray;">
                        <form method="get" action="/wp-admin/edit.php">
                            <input type="hidden" name="post_type" value="ma_form_inputs">
                            <input type="hidden" name="action" value="mark_as_read_forms">
                            <input type="hidden" name="forms_wpnonce" value="<?php echo wp_create_nonce('mark_as_read_forms'); ?>">
                            <input type="submit" value="Markeer alle inzendingen als gelezen" class="button">
                        </form>
                    </div>
                </div>
                <?php
            });
        }
    }

    public function disable_gutenberg($can_edit, $post_type)
    {
        global $_GET;
        if (!$can_edit) {
            return $can_edit;
        }

        if (isset($_GET['post']) && $post_type === 'ma_forms' && get_post_meta($_GET['post'], 'form_type', true) === 'html') {
            return false;
        }

        return $can_edit;
    }

    public function disable_classic()
    {
        if (!isset($_GET['post'])) {
            return;
        }

        $post = get_post($_GET['post']);
        if ($post->post_type === 'ma_forms' && get_post_meta($_GET['post'], 'form_type', true) === 'html') {
            remove_post_type_support('ma_forms', 'editor');
        }
    }

    public function gutenberg_blocks($block_types, $block_editor_context)
    {
        $allowed = [
            'core/paragraph',
            'core/columns',
            'core/image',
            'madeitforms/input-field',
            'madeitforms/upload-field',
            'madeitforms/largeinput-field',
            'madeitforms/submit-field',
            'madeitforms/multi-value-field',
            'madeitforms/radio-value-field',
            'madeitforms/question-seperator',
            'core/spacer',
        ];
        if ($block_editor_context->post->post_type == 'ma_forms') {
            return $allowed;
        }

        return $block_types;
    }

    public function bulk_action_ma_form_inputs($bulk_actions)
    {
        $bulk_actions['mark-as-read'] = __('Mark as read', 'forms-by-made-it');

        return $bulk_actions;
    }

    public function handle_bulk_action_ma_form_inputs($redirect_url, $action, $post_ids)
    {
        if ($action == 'mark-as-read') {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, 'read', 1);
            }
            $redirect_url = add_query_arg('changed-mark-as-read', count($post_ids), $redirect_url);
        }

        return $redirect_url;
    }

    public function notice_mark_as_read()
    {
        if (!empty($_REQUEST['changed-mark-as-read'])) {
            $num_changed = (int) $_REQUEST['changed-mark-as-read'];
            /* translators: %s: Number of changed posts */
            printf('<div id="message" class="updated notice is-dismissable"><p>'.__('%d submits marked as read.', 'forms-by-made-it').'</p></div>', $num_changed);
        }
    }

    public function addHooks()
    {
        add_action('admin_init', [$this, 'initAdmin']);
        add_action('admin_menu', [$this, 'initMenu']);
        add_action('admin_enqueue_scripts', [$this, 'initStyle']);

        add_action('init', [$this, 'init']);
        add_action('admin_footer', [$this, 'admin_footer']);

        add_filter('manage_edit-ma_forms_columns', [$this, 'set_custom_edit_ma_forms_columns']);
        add_action('manage_ma_forms_posts_custom_column', [$this, 'custom_ma_forms_column'], 10, 2);

        add_filter('manage_edit-ma_form_inputs_columns', [$this, 'set_custom_edit_ma_form_inputs_columns']);
        add_action('manage_ma_form_inputs_posts_custom_column', [$this, 'custom_ma_form_inputs_column'], 10, 2);

        add_action('edit_form_after_title', [$this, 'edit_form_after_title'], 10, 1);
        add_action('edit_form_advanced', [$this, 'edit_form_advanced'], 10, 1);
        add_action('submitpost_box', [$this, 'submitpost_box'], 20, 1);

        add_action('save_post_ma_forms', [$this, 'save_form'], 10, 3);
        add_action('save_post', [$this, 'save_meta'], 10, 3);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);

        add_action('admin_menu', [$this, 'admin_menu']);

        add_action('load-edit.php', [$this, 'form_inputs_export_button']);

        add_filter('gutenberg_can_edit_post_type', [$this, 'disable_gutenberg'], 10, 2);
        add_filter('use_block_editor_for_post_type', [$this, 'disable_gutenberg'], 10, 2);
        add_action('init', [$this, 'disable_classic']);

        add_filter('allowed_block_types_all', [$this, 'gutenberg_blocks'], 10, 2);

        add_filter('bulk_actions-edit-ma_form_inputs', [$this, 'bulk_action_ma_form_inputs']);
        add_filter('handle_bulk_actions-edit-ma_form_inputs', [$this, 'handle_bulk_action_ma_form_inputs'], 10, 3);

        add_action('admin_notices', [$this, 'notice_mark_as_read']);

        add_action('restrict_manage_posts', [$this, 'add_custom_filters_to_form_inputs_table']);
        add_action('pre_get_posts', [$this, 'filter_form_inputs']);

        
        add_action('wp_ajax_ma_forms_resend_mail', [$this, 'resendMail']);
    }

    public function generateKey()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function enterToDB($data)
    {
        $data = str_replace('\r\n', '|--MAFORM-RN--|', $data);
        $data = str_replace('\r', '|--MAFORM-R--|', $data);
        $data = str_replace('\n', '|--MAFORM-N--|', $data);

        return $data;
    }

    public function dbToEnter($data)
    {
        $data = str_replace('|--MAFORM-RN--|', '\r\n', $data);
        $data = str_replace('|--MAFORM-R--|', '\r', $data);
        $data = str_replace('|--MAFORM-N--|', '\n', $data);

        $data = preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $data);

        return $data;
    }

    public function add_custom_filters_to_form_inputs_table()
    {
        global $typenow, $_GET;
        if ($typenow == 'ma_form_inputs') {
            $forms = get_posts([
                'post_type'   => 'ma_forms',
                'numberposts' => -1,
            ]); ?>
            <select name="ma_forms" id="ma_forms" class="postform">
                <option value=""><?php echo __('All forms', 'forms-by-made-it'); ?></option>
                <?php
                foreach ($forms as $form) {
                    ?>
                    <option value="<?php echo $form->ID; ?>"
                        <?php if (isset($_GET['ma_forms']) && $_GET['ma_forms'] == $form->ID) {
                        echo 'SELECTED';
                    } ?>
                        ><?php echo esc_textarea($form->post_title); ?></option>
                    <?php
                } ?>
            </select>
            <?php
        }
    }

    public function filter_form_inputs($query)
    {
        if (is_admin() && $query->is_main_query() && in_array($query->get('post_type'), ['ma_form_inputs'])) {
            if (isset($_GET['ma_forms'])) {
                $query->set('meta_key', 'form_id');
                $query->set('meta_value', $_GET['ma_forms']);
            }
        }
    }

    public function getMaxInputFields()
    {
        $maxValue = 0;

        $forms = get_posts([
            'post_type'   => 'ma_forms',
            'numberposts' => -1,
        ]);

        foreach ($forms as $form) {
            $aantal = count($this->getInputFieldsOfForm($form->ID));

            if ($aantal > $maxValue) {
                $maxValue = $aantal;
            }
        }

        return $maxValue;
    }

    public function getInputFieldsOfForm($formId)
    {
        $form = get_post_meta($formId, 'form', true);

        $tags = [];
        $tag = null;
        foreach (explode('[', $form) as $i => $v) {
            $v = trim($v);
            if ($i > 0 && strlen($v) > 0) {
                $posName = strpos($v, 'name="');
                if ($posName > 0) {
                    $tag = substr($v, $posName + 6);
                    $tag = substr($tag, 0, strpos($tag, '"'));
                    $tags[] = $tag;
                }
            }
        }
        
        if($tag) {
            preg_match_all('/\['.$tag.'.*name="'.$name.'".*\]/', $form, $result);
            if (isset($result[0][0])) {
                $partWithTag = $result[0][0];

                $key = '';
                foreach (explode('="', $partWithTag) as $o) {
                    if ($key == '') {
                        $space = explode(' ', $o);
                        if (count($space) <= 1) {
                            $key = $space[0];
                        } else {
                            $key = $space[count($space) - 1];
                        }
                    } else {
                        $tags[$key] = substr($o, 0, strpos($o, '"'));
                        $key = trim(substr($o, strpos($o, '"') + 1));
                    }
                }

                return $tags;
            }
        }

        return $tags;
    }
}
