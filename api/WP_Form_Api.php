<?php

class WP_Form_Api
{
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
        $forms = get_posts([
            'post_type'  => 'ma_forms',
            'meta_query' => [
                [
                    'key'   => 'form_id',
                    'value' => $id,
                ],
            ],
        ]);

        if (count($forms) === 1) {
            $form = $forms[0];
        } else {
            $form = get_post($id);
        }

        if (!$form || $form->post_type !== 'ma_forms' || !is_array($data)) {
            return false;
        }

        $formValue = get_post_meta($form->ID, 'form', true);
        $formValue = str_replace('\"', '"', $formValue);
        if (isset($form->ID)) {
            //validate input fields
            $error = false;
            $error_msg = '';
            $messages = json_decode(get_post_meta($form->ID, 'messages', true), true);

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

            $inputId = -1;
            if (get_post_meta($form->ID, 'save_inputs', true) == 1) {
                $inputId = wp_insert_post([
                    'post_title'  => 'Form submit '.$form->post_title.' - '.$this->getIP(),
                    'post_status' => 'publish',
                    'post_type'   => 'ma_form_inputs',
                ]);

                $postData['input_id'] = $inputId;

                update_post_meta($inputId, 'form_id', $form->ID);
                update_post_meta($inputId, 'data', wp_slash(wp_json_encode($postData)));
                update_post_meta($inputId, 'ip', $this->getIP());
                update_post_meta($inputId, 'user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN');
                update_post_meta($inputId, 'spam', $spam ? 1 : 0);
                update_post_meta($inputId, 'read', 0);
                update_post_meta($inputId, 'result', '');
            }

            //execute actions
            $actions = json_decode(get_post_meta($form->ID, 'actions', true), true);
            if (is_array($actions) && count($actions) > 0) {
                $formActions = apply_filters('madeit_forms_submit_actions', $actions);
                foreach ($formActions as $actID => $actionInfo) {
                    $action = $this->actions[$actionInfo['_id']];

                    $data = [
                        'id' => $inputId,
                    ];
                    foreach ($action['action_fields'] as $name => $info) {
                        $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value'];
                        $data[$name] = $this->changeInputTag($inputValue, $postData);
                    }

                    if (is_callable($action['callback'])) {
                        $result = call_user_func($action['callback'], $data, $messages, $actionInfo);
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

    private function changeInputTag($value, $params = [])
    {
        if (count($params) === 0) {
            $params = $_POST;
        }
        $value = $this->replaceDynamicTokens($value);
        $value = $this->applyDynamicFieldBlocks($value, $params);
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $v = implode(', ', $v);
            }
            $value = str_replace('['.$k.']', $v, $value);
        }

        return $value;
    }

    private function replaceDynamicTokens($value)
    {
        $timestamp = current_time('timestamp');
        $date = date_i18n('j F Y', $timestamp);
        $time = date_i18n('G:i', $timestamp);
        $siteUrl = home_url();
        $siteUrlEscaped = esc_url($this->formatWebsiteUrl($siteUrl));
        $siteLabel = esc_html($this->formatWebsiteLabel($siteUrl));
        $value = str_replace('{{DATUM}}', $date, $value);
        $value = str_replace('{{TIJD}}', $time, $value);
        $value = str_replace('{{DATUM_TIJD}}', $date.' '.$time, $value);
        $value = str_replace('{{WEBSITE}}', '<a href="'.$siteUrlEscaped.'">'.$siteLabel.'</a>', $value);

        return $value;
    }

    private function formatWebsiteLabel($url)
    {
        $parsed = wp_parse_url($url);
        if (is_array($parsed) && !empty($parsed['host'])) {
            return $this->ensureWwwHost($parsed['host']);
        }

        return preg_replace('#^https?://#', '', (string) $url);
    }

    private function formatWebsiteUrl($url)
    {
        $parsed = wp_parse_url($url);
        if (is_array($parsed) && !empty($parsed['host'])) {
            $host = $this->ensureWwwHost($parsed['host']);
            $path = isset($parsed['path']) ? $parsed['path'] : '';

            return $host.$path;
        }

        return preg_replace('#^https?://#', '', (string) $url);
    }

    private function ensureWwwHost($host)
    {
        $host = trim((string) $host);
        if ($host === '') {
            return $host;
        }

        return strpos($host, 'www.') === 0 ? $host : 'www.'.$host;
    }

    private function applyDynamicFieldBlocks($value, $params)
    {
        $value = $this->renderForeachBlocks($value, $params);
        $value = $this->renderIfBlocks($value, $params);

        return $value;
    }

    private function renderForeachBlocks($value, $params)
    {
        $pattern = '/{{foreach\s+([^}]+)}}(.*?){{endforeach}}/s';

        return preg_replace_callback($pattern, function ($matches) use ($params) {
            $field = trim($matches[1]);
            $rawValue = isset($params[$field]) ? $params[$field] : null;

            if (is_array($rawValue)) {
                $items = $rawValue;
            } else {
                $rawValue = isset($rawValue) ? trim((string) $rawValue) : '';
                $items = $rawValue === '' ? [] : [$rawValue];
            }

            if (count($items) === 0) {
                return '';
            }

            $output = '';
            foreach ($items as $item) {
                $chunk = str_replace('['.$field.']', $item, $matches[2]);
                $output .= $chunk;
            }

            return $output;
        }, $value);
    }

    private function renderIfBlocks($value, $params)
    {
        $pattern = '/{{if\s+([^}]+)}}((?:(?!{{if\s).)*?){{endif}}/s';
        $previous = null;

        while ($previous !== $value) {
            $previous = $value;
            $value = preg_replace_callback($pattern, function ($matches) use ($params) {
                if (!$this->evaluateIfCondition(trim($matches[1]), $params)) {
                    return '';
                }

                return $matches[2];
            }, $value);
        }

        return $value;
    }

    private function evaluateIfCondition($condition, $params)
    {
        if (strpos($condition, '===') !== false || strpos($condition, '!==') !== false) {
            $operator = strpos($condition, '!==') !== false ? '!==' : '===';
            $parts = preg_split('/\s*'.preg_quote($operator, '/').'\s*/', $condition, 2);
            $field = isset($parts[0]) ? trim($parts[0]) : '';
            $expectedRaw = isset($parts[1]) ? trim($parts[1]) : '';

            if ($field === '') {
                return false;
            }

            $expected = $this->stripConditionQuotes($expectedRaw);
            $rawValue = isset($params[$field]) ? $params[$field] : null;
            $isMatch = $this->matchesExpectedValue($rawValue, $expected);

            return $operator === '===' ? $isMatch : !$isMatch;
        }

        $field = trim($condition);
        $rawValue = isset($params[$field]) ? $params[$field] : null;

        return $this->isFilledValue($rawValue);
    }

    private function stripConditionQuotes($value)
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    private function matchesExpectedValue($rawValue, $expected)
    {
        if (is_array($rawValue)) {
            foreach ($rawValue as $item) {
                if (trim((string) $item) === $expected) {
                    return true;
                }
            }

            return false;
        }

        if ($rawValue === null) {
            return false;
        }

        return trim((string) $rawValue) === $expected;
    }

    private function isFilledValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (trim((string) $item) !== '') {
                    return true;
                }
            }

            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
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
                $spaces = explode(' ', array_last($tags));
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
                        $key = array_last($space);
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
