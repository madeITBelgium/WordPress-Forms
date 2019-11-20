<?php

class WP_Form_Api
{
    private $db;
    private $tags = [];
    private $actions = [];
    private $messages = [];
    private $settings;
    private $defaultSettings;
    private $form_id = null;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->defaultSettings = $this->settings->loadDefaultSettings();
        $this->db = \WeDevs\ORM\Eloquent\Database::instance();
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
    }

    public function save($id, $data)
    {
        $form = $this->db->table('madeit_forms')->where('id', $id)->first();
        $formValue = $form->form;
        $formValue = str_replace('\"', '"', $formValue);
        if (isset($form->id)) {
            //validate input fields
            $error = false;
            $error_msg = '';
            $messages = json_decode($form->messages, true);

            //insert form input
            foreach ($data as $k => $v) {
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
                return $error_msg;
            }

            //check spam
            $spam = false;

            //insert into DB
            $postData = $data;
            unset($postData['form_id']);
            $dbI = $this->db->table('madeit_form_inputs')->insert([
                    'form_id' => $form->id,
                    'data' => json_encode($postData),
                    'ip' => $this->getIP(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN',
                    'spam' => $spam ? 1 : 0,
                    'read' => 0,
                    'result' => '',
                    'create_time' => date('Y-m-d H:i:s'),
                ]
            );

            //execute actions
            if (isset($form->actions) && !empty($form->actions)/* && count($form->actions) > 0*/) {
                $formActions = json_decode($form->actions, true);
                foreach ($formActions as $actID => $actionInfo) {
                    $action = $this->actions[$actionInfo['_id']];

                    $data = [];
                    foreach ($action['action_fields'] as $name => $info) {
                        $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value'];
                        $data[$name] = $this->changeInputTag($inputValue);
                    }

                    if (is_callable($action['callback'])) {
                        $result = call_user_func($action['callback'], $data, $messages);
                    } else {
                        //can't execute action ...
                    }
                }
            }

            return true;
        }

        return false;
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
