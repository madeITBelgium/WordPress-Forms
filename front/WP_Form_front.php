<?php

class WP_Form_front
{
    private $db;
    private $tags = [];
    private $actions = [];
    private $messages = [];
    private $settings;
    private $defaultSettings;
    private $form_id = null;

    public function __construct($settings, $wp_plugin_db)
    {
        $this->settings = $settings;
        $this->defaultSettings = $this->settings->loadDefaultSettings();
        $this->db = $wp_plugin_db;
    }

    public function init()
    {
        $actions = apply_filters('madeit_forms_actions', []);
        foreach ($actions as $id => $value) {
            $this->addAction($id, $value);
        }

        $modules = apply_filters('madeit_forms_modules', []);
        foreach ($modules as $id => $value) {
            $this->addModule($id, $value);
        }

        wp_register_style('madeit-form-style', MADEIT_FORM_URL.'front/css/style.css', [], null);
        wp_enqueue_style('madeit-form-style');
        if (isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
            wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
        }

        $this->shortCodes();
    }

    public function shortCodes()
    {
        add_shortcode('form', [$this, 'shortcode_form']);
    }

    public function shortcode_form($atts)
    {
        extract(shortcode_atts([
            'id' => 0,
        ], $atts));
        ob_start();

        $form = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $id);
        if (is_array($form)) {
            $form = json_decode(json_encode($form));
        }
        $formValue = $form->form;
        $formValue = str_replace('\"', '"', $formValue);
        if (isset($form->id)) {
            if (isset($_POST['form_id']) && $_POST['form_id'] == $id) {
                //validate input fields
                $error = false;
                $error_msg = '';
                $messages = json_decode($form->messages, true);

                //insert form input
                foreach ($_POST as $k => $v) {
                    $tag = $this->getTagNameFromPostInput($formValue, $k);
                    if ($tag !== false) {
                        if (is_callable($this->tags[$tag]['validation'])) {
                            $tagOptions = $this->getOptionsFromTag($formValue, $tag, $k);
                            $result = call_user_func($this->tags[$tag]['validation'], $tagOptions, $v, $messages);
                            if ($result !== true) {
                                $error = true;
                                $error_msg = $result;
                            }
                        }
                    }
                }

                if ($error) {
                    echo '<div class="madeit-form-error">'.$error_msg.'</div>';
                    $this->renderForm($id, $form);
                    $content = ob_get_clean();

                    return $content;
                }

                //check spam
                $spam = false;

                //insert into DB
                $postData = $_POST;
                unset($postData['form_id']);

                $this->db->queryWrite('INSERT INTO `'.$this->db->prefix().'madeit_form_inputs` (form_id, data, ip, user_agent, spam, `read`, result, create_time) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)',
                                      $form->id, json_encode($postData), $this->getIP(), (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN'), $spam ? 1 : 0, 0, '', date('Y-m-d H:i:s'));

                //execute actions
                if (isset($form->actions) && !empty($form->actions)/* && count($form->actions) > 0*/) {
                    $formActions = apply_filters('madeit_forms_submit_actions', json_decode($form->actions, true));
                    foreach ($formActions as $actID => $actionInfo) {
                        $action = $this->actions[$actionInfo['_id']];

                        $data = [];
                        foreach ($action['action_fields'] as $name => $info) {
                            $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value'];
                            $data[$name] = $this->changeInputTag($inputValue);
                        }

                        if (is_callable($action['callback'])) {
                            $result = call_user_func($action['callback'], $data, $messages, $actionInfo);
                            if (is_array($result) && isset($result['type'])) {
                                if ($result['type'] == 'JS') {
                                    echo '<script>'.$result['code'].'</script>';
                                } elseif ($result['type'] == 'HTML') {
                                    echo str_replace('\"', '"', $result['code']);
                                }
                            } elseif ($result !== true) {
                                $error = true;
                                $error_msg = $result;
                            }
                        } else {
                            //can't execute action ...
                        }
                    }
                }

                if ($error) {
                    echo '<div class="madeit-form-error">'.$error_msg.'</div>';
                    $this->renderForm($id, $form);
                } else {
                    echo '<div class="madeit-form-success">'.$messages['success'].'</div>';
                }
                //return success message
            } else {
                $this->renderForm($id, $form);
            }
        } else {
            echo __("Can't display the form.", 'forms-by-made-it');
        }

        $content = ob_get_clean();

        return $content;
    }

    private function renderForm($id, $form)
    {
        $this->form_id = $id;
        add_filter('madeit_forms_form_id', [$this, 'form_id']);
        echo '<form action="" method="post" id="form_'.$id.'">';
        echo '<input type="hidden" name="form_id" value="'.$id.'">';
        $formValue = $form->form;
        $formValue = str_replace('\"', '"', $formValue);
        echo do_shortcode($formValue);
        echo '</form>';
    }

    public function form_id()
    {
        return $this->form_id;
    }

    private function changeInputTag($value)
    {
        foreach ($_POST as $k => $v) {
            $value = str_replace('['.$k.']', $v, $value);
        }

        return $value;
    }

    private function getTagNameFromPostInput($form, $inputKey)
    {
        if ($inputKey == 'btn_submit' || $inputKey == 'g-recaptcha-response') {
            return 'submit';
        }
        $pos = strpos($form, 'name="'.$inputKey.'"');
        if ($pos !== false) {
            $tags = explode('[', substr($form, 0, $pos));
            if (count($tags) > 0) {
                $spaces = explode(' ', $tags[count($tags) - 1]);
                if (isset($spaces[0])) {
                    return $spaces[0];
                }
            }
        }

        return false;
    }

    private function getOptionsFromTag($form, $tag, $name)
    {
        preg_match_all('/\['.$tag.'.*name="'.$name.'".*\]/', $form, $result);
        if (isset($result[0][0])) {
            $partWithTag = $result[0][0];

            $key = '';
            $data = [];
            foreach (explode('="', $partWithTag) as $o) {
                if ($key == '') {
                    $space = explode(' ', $o);
                    if (count($space) <= 1) {
                        $key = $space[0];
                    } else {
                        $key = $space[count($space) - 1];
                    }
                } else {
                    $data[$key] = substr($o, 0, strpos($o, '"'));
                    $key = trim(substr($o, strpos($o, '"') + 1));
                }
            }

            return $data;
        }

        return false;
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

    public function getIP()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return 'UNKNOWN';
    }

    public function addHooks()
    {
        add_action('init', [$this, 'init']);
    }
}
