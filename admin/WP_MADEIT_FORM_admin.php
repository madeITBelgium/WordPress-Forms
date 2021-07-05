<?php
class WP_MADEIT_FORM_admin
{
    private $db;
    private $cycles;
    private $tags = [];
    private $actions = [];
    private $messages = [];
    private $settings;
    private $defaultSettings;

    public function __construct($settings, $wp_plugin_db)
    {
        $this->settings = $settings;
        $this->defaultSettings = $this->settings->loadDefaultSettings();
        $this->db = $wp_plugin_db;
    }

    public function initMenu()
    {
        global $_wp_last_object_menu;
        $_wp_last_object_menu++;

        $new = '';
        $count = 0;
        $countQry = $this->db->querySingleRecord('SELECT count(*) as aantal FROM '.$this->db->prefix().'madeit_form_inputs WHERE `read` = 0');
        if (isset($countQry['aantal'])) {
            $count = $countQry['aantal'];
        }

        if ($count > 0) {
            $new = "<span class='update-plugins' title='".__('Unread form submits', 'forms-by-made-it')."'><span class='update-count'>".number_format_i18n($count).'</span></span>';
        }

        add_menu_page(__('Forms', 'forms-by-made-it'), __('Forms', 'forms-by-made-it').' '.$new, 'manage_options', 'madeit_forms', [$this, 'show_all'], 'dashicons-email', $_wp_last_object_menu);
        add_submenu_page('madeit_forms', __('Made I.T. Forms', 'forms-by-made-it'), __('Forms', 'forms-by-made-it'), 'manage_options', 'madeit_forms', [$this, 'show_all']);
        add_submenu_page('madeit_forms', __('Made I.T. Forms - New', 'forms-by-made-it'), __('Add new', 'forms-by-made-it'), 'manage_options', 'madeit_form', [$this, 'new_form']);
        add_submenu_page('madeit_forms', __('Made I.T. Forms - Settings', 'forms-by-made-it'), __('Settings', 'forms-by-made-it'), 'manage_options', 'madeit_forms_settings', [$this, 'settings']);

        add_submenu_page('madeit_forms', __('Made I.T. Forms - Inputs', 'forms-by-made-it'), __('Submitted forms', 'forms-by-made-it').' '.$new, 'manage_options', 'madeit_form_input', [$this, 'all_inputs']);
    }

    public function initStyle()
    {
        wp_register_style('madeit-form-admin-style', MADEIT_FORM_URL.'admin/css/style.css', [], null);
        wp_register_style('madeit-tabs', MADEIT_FORM_URL.'admin/css/tabs.css', [], null);
        wp_enqueue_style('madeit-tabs');
        wp_enqueue_style('madeit-form-admin-style');

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('madeit-form-script', MADEIT_FORM_URL.'admin/js/script.js', ['jquery'], 1, true);
        wp_enqueue_script('madeit-tabs', MADEIT_FORM_URL.'admin/js/tabs.js', ['jquery'], 1, true);
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
        if (isset($_GET['page']) && $_GET['page'] === 'madeit_form_input' && $_GET['action'] === 'export' && wp_verify_nonce($_GET['_wpnonce'], 'export_forms')) {
            $data = $this->db->querySelect('SELECT * FROM `'.$this->db->prefix().'madeit_form_inputs` WHERE form_id = %s', $_GET['id']);
            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=export-madeit-forms-'.date('Y-m-d-H-i-s').'.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');

            $d = $data[0];
            unset($row['data']);
            foreach (json_decode($d['data'], true) as $k => $v) {
                $row[$k] = $v;
            }
            unset($row['g-recaptcha-response']);
            $columns = array_keys($row);

            // output the column headings
            fputcsv($output, $columns);

            // fetch the data
            foreach ($data as $d) {
                unset($row['data']);
                foreach (json_decode($d['data'], true) as $k => $v) {
                    $row[$k] = $v;
                }
                unset($row['g-recaptcha-response']);
                fputcsv($output, $row);
            }
            exit();
        }
    }

    public function show_all()
    {
        echo '<div class="wrap">';
        if (!isset($_GET['action']) || $_GET['action'] != 'edit') {
            ?>
            <h1><?php echo __('Forms', 'forms-by-made-it'); ?><a href="admin.php?page=madeit_form" class="add-new-h2"><?php echo __('Add new', 'forms-by-made-it'); ?></a></h1>
            <?php
        }
        if ((isset($_GET['action']) && $_GET['action'] == 'delete') || (isset($_POST['madeit-forms-delete']) && isset($_GET['action']) && $_GET['action'] == 'edit')) {
            $this->db->queryWrite('DELETE FROM '.$this->db->prefix().'madeit_forms WHERE `id` = %s', $_GET['id']); ?>
            <div class="updated"><p><strong><?php echo __('The form is deleted.', 'forms-by-made-it'); ?></strong></p></div>
            <?php
            $_GET['action'] = 'delete';
        }
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            if (isset($_POST['add_new']) && $_POST['add_new'] == 'Y') {
                $this->post_edit_form();
            }
            $f = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $_GET['id']);
            if (is_array($f)) {
                $f = json_decode(json_encode($f));
            }
            if (!isset($f->id)) {
                ?>
                <div class="error"><p><strong><?php echo __('The form isn\'t available', 'forms-by-made-it'); ?></strong></p></div>
                <?php
            } else {
                $form = ['id' => $f->id, 'title' => $f->title, 'form' => $f->form, 'actions' => json_decode($f->actions, true), 'messages' => json_decode($f->messages, true)];
                include MADEIT_FORM_DIR.'/admin/forms/form.php';
            }
        } else {
            require_once MADEIT_FORM_DIR.'/admin/FormListTable.php';
            $list = new FormListTable();
            $list->prepare_items();
            $list->display();
        }
        echo '</div>';
    }

    public function new_form()
    {
        $from = '<p>Your name:</p>
[text name="your-name"]
<p>Your email:</p>
[email name="your-email"]
[submit value="Send"]';
        if (isset($_POST['add_new'])) {
            if ($_POST['form_id'] == 0) {
                $form = $this->post_form();
            } else {
                $this->post_edit_form();
                $f = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $_POST['form_id']);
                $form = ['id' => $f['id'], 'title' => $f['title'], 'form' => $f['form'], 'actions' => json_decode($f['actions'], true), 'messages' => json_decode($f['messages'], true)];
            }
        } else {
            $form = ['id' => 0, 'title' => '', 'form' => $from, 'actions' => [], 'messages' => []];
        }
        include_once MADEIT_FORM_ADMIN.'/forms/form.php';
    }

    private function post_form()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'madeit-form-save-contact-form')) {
            exit(__('Security check'));
        }
        $form = ['id' => $_POST['form_id'], 'title' => $_POST['title'], 'form' => $_POST['form'], 'actions' => [], 'messages' => []];
        $error = false;
        $error_msg = '';

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
                $form['actions'][$j] = ['_id' => $id];
                foreach ($action['action_fields'] as $name => $info) {
                    $form['actions'][$j][$name] = isset($_POST['action_'.$id.'_'.$name.'_'.$i]) ? $_POST['action_'.$id.'_'.$name.'_'.$i] : '';
                }
                $form['actions'][$j]['key'] = isset($_POST['action_key_'.$i]) ? $_POST['action_key_'.$i] : $this->generateKey();
            }
            $j++;
        }

        foreach ($_POST as $k => $v) {
            if (substr($k, 0, strlen('messages_')) == 'messages_') {
                $form['messages'][substr($k, strlen('messages_'))] = $v;
            }
        }

        if (!$error) {
            $this->db->queryWrite('INSERT INTO '.$this->db->prefix().'madeit_forms (title, form, actions, messages, create_time) VALUES (%s, %s, %s, %s, %s)', $form['title'], $form['form'], json_encode($form['actions']), json_encode($form['messages']), date('Y-m-d H:i:s'));

            $f = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE title = %s ORDER BY id DESC', $form['title']);
            if (!isset($f['id'])) {
                ?>
                <div class="error"><p><strong><?php echo __('The form doesn\'t exist', 'forms-by-made-it'); ?></strong></p></div>
                <?php
            } else {
                $form = ['id' => $f['id'], 'title' => $f['title'], 'form' => $f['form'], 'actions' => json_decode($f['actions'], true), 'messages' => json_decode($f['messages'], true)];
            } ?>
            <div class="updated"><p><strong><?php echo __('The form is successfully saved.', 'forms-by-made-it'); ?></strong></p></div>
            <?php
        } else {
            ?>
            <div class="error"><p><strong><?php echo __($error, 'forms-by-made-it'); ?></strong></p></div>
            <?php
        }

        return $form;
    }

    private function post_edit_form()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'madeit-form-save-contact-form')) {
            wp_die(__('Security check'));
        }
        $form = ['id' => $_POST['form_id'], 'title' => $_POST['title'], 'form' => $_POST['form'], 'actions' => [], 'messages' => []];
        $error = false;
        $error_msg = '';

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
                $form['actions'][$j] = ['_id' => $id];
                foreach ($action['action_fields'] as $name => $info) {
                    $form['actions'][$j][$name] = isset($_POST['action_'.$id.'_'.$name.'_'.$i]) ? $_POST['action_'.$id.'_'.$name.'_'.$i] : '';
                }
                $form['actions'][$j]['key'] = isset($_POST['action_key_'.$i]) ? $_POST['action_key_'.$i] : $this->generateKey();
            }
            $j++;
        }

        foreach ($_POST as $k => $v) {
            if (substr($k, 0, strlen('messages_')) == 'messages_') {
                $form['messages'][substr($k, strlen('messages_'))] = $v;
            }
        }

        if ($error) {
            ?>
            <div class="error"><p><strong><?php echo __($error_msg, 'forms-by-made-it'); ?></strong></p></div>
            <?php
        } else {
            $this->db->queryWrite('UPDATE '.$this->db->prefix().'madeit_forms set title = %s, form = %s, actions = %s, messages = %s WHERE id = %s', $form['title'], $form['form'], json_encode($form['actions']), json_encode($form['messages']), $_POST['form_id']); ?>
            <div class="updated"><p><strong><?php echo __('The form is successfully saved.', 'forms-by-made-it'); ?></strong></p></div>
            <?php
        }
    }

    public function all_inputs()
    {
        echo '<div class="wrap">';
        if (!isset($_GET['action']) || $_GET['action'] != 'edit') {
            ?>
            <h1><?php echo __('Submitted forms', 'forms-by-made-it'); ?></h1>
            <?php
        }
        if (isset($_GET['action']) && $_GET['action'] == 'delete') {
            $this->db->queryWrite('DELETE FROM '.$this->db->prefix().'madeit_form_inputs WHERE id = %s', $_GET['id']); ?>
            <div class="updated"><p><strong><?php echo __('The submitted data is deleted.', 'forms-by-made-it'); ?></strong></p></div>
            <?php
        }
        if (isset($_GET['action']) && $_GET['action'] == 'show') {
            $f = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_form_inputs` WHERE id = %s', $_GET['id']);
            $form = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $f['form_id']);

            $this->db->queryWrite('UPDATE '.$this->db->prefix().'madeit_form_inputs set `read` = 1 WHERE id = %s', $_GET['id']);
            if (!isset($f['id'])) {
                ?>
                <div class="error"><p><strong><?php echo __('The data isn\'t available', 'forms-by-made-it'); ?></strong></p></div>
                <?php
            } else {
                include MADEIT_FORM_DIR.'/admin/forms/submitted.php';
            }
        } else {
            $forms = $this->db->querySelect('SELECT * FROM `'.$this->db->prefix().'madeit_forms`'); ?>
            <form method="get" action="/wp-admin/admin.php">
                <?php wp_nonce_field('export_forms'); ?>
                <input type="hidden" name="page" value="madeit_form_input">
                <input type="hidden" name="action" value="export">
                <select name="id">
                    <?php foreach ($forms as $form) { ?>
                    <option value="<?php echo $form['id']; ?>"><?php echo $form['title']; ?></option>
                    <?php } ?>
                </select>
                <input type="submit" value="Exporteer" class="button">
            </form>
            <?php

            require_once MADEIT_FORM_DIR.'/admin/InputListTable.php';
            $list = new InputListTable($this->db);
            $list->prepare_items();
            $list->display();
        }
        echo '</div>';
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

        $form = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $id);
        if (isset($form['id'])) {
            $formValue = $form->form;
            $formValue = str_replace('\"', '"', $formValue);
            if (isset($form['id'])) {
                $tags = $this->getTags($formValue);
                $t = [];
                foreach ($tags as $a) {
                    $t[$a] = 'a';
                }

                //execute actions
                if (isset($form['actions']) && !empty($form['actions'])/* && count($form->actions) > 0*/) {
                    $formActions = json_decode($form['actions'], true);
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
            }
        }
        //print_r($errors);
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

    public function addHooks()
    {
        add_action('admin_init', [$this, 'initAdmin']);
        add_action('admin_menu', [$this, 'initMenu']);
        add_action('admin_enqueue_scripts', [$this, 'initStyle']);

        add_action('init', [$this, 'init']);
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
}
