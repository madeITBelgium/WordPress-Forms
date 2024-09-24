<?php

class WP_Form_front
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

        if (!is_admin()) {
            wp_register_style('madeit-form-style', MADEIT_FORM_URL.'front/css/style.css', [], null);
            wp_enqueue_style('madeit-form-style');
            if (isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
                wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
            }
            wp_enqueue_script('madeit-form-script', MADEIT_FORM_URL.'front/js/script.js', ['jquery'], null, true);
        }
        $this->shortCodes();
    }

    public function shortCodes()
    {
        add_shortcode('form', [$this, 'shortcode_form']);
    }

    public function shortcode_form($attsOrig)
    {
        $atts = shortcode_atts(['id' => 0, 'ajax' => 'no', 'extra_id' => null, 'spam_action' => 'fail'], $attsOrig);
        extract($atts);

        $ajax = strtolower($ajax) == 'yes';

        $forms = get_posts([
            'post_type'   => 'ma_forms',
            'status'      => 'published',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'     => 'form_id',
                    'compare' => '=',
                    'value'   => ''.$id,
                ],
            ],
        ]);

        $translatedForm = null;
        if (count($forms) === 1) {
            $form = $forms[0];
            $translatedForm = $form;
        } else {
            $form = get_post($id);

            $objectId = apply_filters('wpml_object_id', $id, 'ma_forms');
            if (!empty($objectId) && $objectId !== $id) {
                //Translation found
                $translatedForm = get_post($objectId);
                if ($translatedForm == null) {
                    $translatedForm = $form;
                }
            } else {
                $translatedForm = $form;
            }
        }

        if ($form->post_type !== 'ma_forms') {
            return __("Can't display the form.", 'forms-by-made-it');
        }
        $this->form_id = $form->ID;

        ob_start();

        if (isset($_POST['form_id']) && $_POST['form_id'] == $form->ID) {
            //check spam
            $spam = false;

            //validate input fields
            $error = false;
            $error_msg = '';
            $messages = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($form->ID, 'messages', true))), true);

            //insert form input
            $tags = [];
            if (get_post_meta($form->ID, 'form_type', true) === 'html') {
                $formValue = get_post_meta($form->ID, 'form', true);
                $formValue = str_replace('\"', '"', $formValue);
                $formValue = str_replace("\'", "'", $formValue);
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
            } else {
                $blocks = parse_blocks($form->post_content);
                $blocks = $this->parseBlocks($blocks);

                foreach ($blocks as $block) {
                    if (isset($block['attrs']['name'])) {
                        $tag = $block['attrs']['name'];
                        $tags[] = $tag;
                        $type = $block['attrs']['type'] ?? 'text';

                        $label = $block['attrs']['label'] ?? ($block['attrs']['placeholder'] ?? $tag);

                        if (isset($block['attrs']['required']) && $block['attrs']['required']) {
                            if ($block['blockName'] === 'madeitforms/upload-field') {
                                if (!isset($_FILES[$tag]) || empty($_FILES[$tag])) {
                                    $error = true;
                                    $error_msg = $messages['invalid_required'].' ('.$label.')';
                                }
                            } elseif (!isset($_POST[$tag]) || empty($_POST[$tag])) {
                                $error = true;
                                $error_msg = $messages['invalid_required'].' ('.$label.')';
                            }
                        }

                        if (!empty($_POST[$tag]) && $type === 'email') {
                            if (!filter_var($_POST[$tag], FILTER_VALIDATE_EMAIL)) {
                                $error = true;
                                $error_msg = isset($messages['mod_text_invalid_email']) ? $messages['mod_text_invalid_email'] : $messages['validation_error'];
                                $error_msg .= ' ('.$label.')';
                            }
                        }

                        if (!empty($_POST[$tag]) && $type === 'url') {
                            if (!filter_var($_POST[$tag], FILTER_VALIDATE_URL)) {
                                $error = true;
                                $error_msg = isset($messages['mod_text_invalid_url']) ? $messages['mod_text_invalid_url'] : $messages['validation_error'];
                                $error_msg .= ' ('.$label.')';
                            }
                        }

                        if (!empty($_POST[$tag]) && $type === 'tel') {
                            if (!preg_match('%^[+]?[0-9()/ -]*$%', $_POST[$tag])) {
                                $error = true;
                                $error_msg = isset($messages['mod_text_invalid_phone']) ? $messages['mod_text_invalid_phone'] : $messages['validation_error'];
                                $error_msg .= ' ('.$label.')';
                            }
                        }

                        if (!empty($_POST[$tag]) && $type === 'number') {
                            if (!is_numeric($_POST[$tag])) {
                                $error = true;
                                $error_msg = isset($messages['mod_number_invalid_number']) ? $messages['mod_number_invalid_number'] : $messages['validation_error'];
                                $error_msg .= ' ('.$label.')';
                            }

                            if (isset($block['attrs']['minimum']) && $_POST[$tag] < $block['attrs']['minimum']) {
                                $error = true;
                                $error_msg = isset($messages['mod_number_number_too_small']) ? $messages['mod_number_number_too_small'] : $messages['validation_error'];
                                $error_msg .= ' ('.$label.')';
                            }

                            if (isset($block['attrs']['maximum']) && $_POST[$tag] > $block['attrs']['maximum']) {
                                $error = true;
                                $error_msg = isset($messages['mod_number_number_too_large']) ? $messages['mod_number_number_too_large'] : $messages['validation_error'];
                                $error_msg .= ' ('.$label.')';
                            }
                        }
                    }
                }
            }

            if (isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
                $secretKey = $this->defaultSettings['reCaptcha']['secret'];
                if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
                    $error = true;
                    $error_msg = __("The captcha couldn't validate you.", 'forms-by-made-it');
                }
            }

            if ($this->isSpam($_POST)) {
                $spam = true;
                if ($spam_action === 'fail') {
                    $error = true;
                    $error_msg = __('Spam detected.', 'forms-by-made-it');
                }
            }

            $spamScore = null;
            if (isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
                $secretKey = $this->defaultSettings['reCaptcha']['secret'];
                if (!isset($_POST['g-recaptcha-response'])) {
                    $error = true;
                    $error_msg = isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", 'forms-by-made-it');
                }
                $response = $_POST['g-recaptcha-response'];
                $remoteIp = $_SERVER['REMOTE_ADDR'];
                $reCaptchaValidationUrl = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$response&remoteip=$remoteIp");
                $result = json_decode($reCaptchaValidationUrl, true);

                if ($this->defaultSettings['reCaptcha']['version'] === 'V3') {
                    $spamScore = $result['score'] ?? 0;
                    if ($result['score'] < $this->defaultSettings['reCaptcha']['minScore']) {
                        $error = true;
                        $error_msg = isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", 'forms-by-made-it');
                    }
                } else {
                    if ($result['success'] != 1) {
                        $error = true;
                        $error_msg = isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", 'forms-by-made-it');
                    }
                }
            }

            if (get_post_meta($form->ID, 'max_submits', true) == 1 && $this->hasAlreadyCompletedThisForm($form->ID)) {
                $error = true;
                $error_msg = isset($messages['already_submitted']) ? $messages['already_submitted'] : __('You have already submitted this form.', 'forms-by-made-it');
            }

            if ($error) {
                $this->notifyError($error_msg);

                echo '<div class="madeit-form-error">'.$error_msg.'</div>';
                $this->renderForm($form->ID, $form, $translatedForm, $ajax, $extra_id);
                $content = ob_get_clean();

                return $content;
            }

            //set cookie
            $submittedTimes = isset($_COOKIE['madeit_form_'.$form->ID.'_submitted']) ? $_COOKIE['madeit_form_'.$form->ID.'_submitted'] : 0;
            setcookie('madeit_form_'.$form->ID.'_submitted', $submittedTimes, time() + 31556926);

            //insert into DB
            $postData = $_POST;
            if ($spamScore !== null) {
                $postData['spamScore'] = $spamScore;
            }
            unset($attsOrig['ajax']);
            unset($attsOrig['id']);
            $postData = array_merge($attsOrig, $postData);
            unset($postData['form_id']);
            foreach ($postData as $k => $v) {
                if (!in_array($k, $tags)) {
                    $postData[] = null;
                }
            }

            $inputId = -1;
            if (get_post_meta($form->ID, 'save_inputs', true) == 1) {
                $inputId = wp_insert_post([
                    'post_title'  => 'Form submit '.$form->post_title.' - '.$this->getIP(),
                    'post_status' => 'publish',
                    'post_type'   => 'ma_form_inputs',
                ]);

                /* Process file upload */
                $uploadDir = wp_upload_dir();
                $uploadDir = $uploadDir['basedir'].'/madeit-forms/'.$form->ID.'/'.$inputId.'/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES as $k => $v) {
                    //upload file and give URL
                    $url = '';
                    $file = $_FILES[$k];

                    //get file extension
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

                    //generate random filename
                    $filename = md5($file['name'].time()).'.'.$ext;

                    //move file to upload dir
                    move_uploaded_file($file['tmp_name'], $uploadDir.$filename);

                    $url = home_url().'/wp-content/uploads/madeit-forms/'.$form->ID.'/'.$inputId.'/'.$filename;

                    $postData[$k] = $url;
                }
                /* End process file upload */

                $postData = apply_filters('madeit_forms_post_data', $postData, $form->ID, $inputId);
                $postData = apply_filters('madeit_forms_'.$form->ID.'_post_data', $postData, $inputId);

                update_post_meta($inputId, 'form_id', $form->ID);
                update_post_meta($inputId, 'data', $this->enterToDB(json_encode($postData)));
                update_post_meta($inputId, 'ip', $this->getIP());
                update_post_meta($inputId, 'user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN');
                update_post_meta($inputId, 'spam', $spam ? 1 : 0);
                update_post_meta($inputId, 'read', 0);
                update_post_meta($inputId, 'result', '');
            }

            //execute actions
            $actions = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($form->ID, 'actions', true))), true);
            if (is_array($actions) && count($actions) > 0) {
                $formActions = apply_filters('madeit_forms_submit_actions', $actions);
                foreach ($formActions as $actID => $actionInfo) {
                    $action = $this->actions[$actionInfo['_id']];

                    unset($attsOrig['ajax']);
                    unset($attsOrig['id']);
                    $data = array_merge($attsOrig, [
                        'id' => $inputId,
                    ]);
                    foreach ($action['action_fields'] as $name => $info) {
                        $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value'];
                        $data[$name] = $this->changeInputTag($inputValue, $postData);
                    }

                    if (is_callable($action['callback'])) {
                        $data = apply_filters('madeit_forms_action_data', $data, $form->ID, $inputId, $actionInfo, $postData);
                        $result = call_user_func($action['callback'], $data, $messages, $actionInfo, $form->ID, $inputId, $postData);
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
                    }
                }
            }

            if ($error) {
                $this->notifyError($error_msg);
                echo '<div class="madeit-form-error">'.$error_msg.'</div>';
                $this->renderForm($id, $form, $translatedForm, $ajax, $extra_id);
            } else {
                echo '<div class="madeit-form-success">'.$messages['success'].'</div>';
            }
        //return success message
        } else {
            $this->renderForm($form->ID, $form, $translatedForm, $ajax, $extra_id);
        }

        $content = ob_get_clean();

        return $content;
    }

    private function parseBlocks($blocks)
    {
        $return = [];

        foreach ($blocks as $block) {
            if (isset($block['attrs']['name'])) {
                $return[] = $block;
            }

            if (isset($block['innerBlocks']) && count($block['innerBlocks']) > 0) {
                $return = array_merge($return, $this->parseInnerBlocks($block['innerBlocks']));
            }
        }

        return $return;
    }

    private function parseInnerBlocks($blocks)
    {
        $return = [];

        foreach ($blocks as $block) {
            if (isset($block['attrs']['name'])) {
                $return[] = $block;
            }

            if (isset($block['innerBlocks']) && count($block['innerBlocks']) > 0) {
                $return = array_merge($return, $this->parseBlocks($block['innerBlocks']));
            }
        }

        return $return;
    }

    private function renderForm($id, $form, $translatedForm, $ajax = false, $extra_id = null)
    {
        if ($form->post_status !== 'publish') {
            echo __('This form is not available.', 'forms-by-made-it');

            return;
        }

        ob_start();

        $this->form_id = $id;
        add_filter('madeit_forms_form_id', [$this, 'form_id']);
        $formHtmlId = 'form_'.$id;
        if ($extra_id) {
            $formHtmlId .= '_'.$extra_id;
        }
        echo '<form action="" method="post" id="'.$formHtmlId.'" ';

        if (strpos($translatedForm->post_content, 'type="file"') !== false) {
            echo 'enctype="multipart/form-data" class="madeit-forms-noajax"';
        } elseif ($ajax) {
            echo 'class="madeit-forms-ajax"';
        } else {
            echo 'class="madeit-forms-noajax"';
        }

        echo '>';

        echo '<input type="hidden" name="form_id" value="'.$id.'">';
        if (get_post_meta($form->ID, 'form_type', true) === 'html') {
            $formValue = get_post_meta($form->ID, 'form', true);
            $formValue = str_replace('\"', '"', $formValue);
            $formValue = str_replace("\'", "'", $formValue);
            echo do_shortcode($formValue);
        } else {
            $content = apply_filters('the_content', $translatedForm->post_content);

            $blocks = parse_blocks($form->post_content);
            $blocks = $this->parseBlocks($blocks);

            foreach ($blocks as $block) {
                if (isset($block['attrs']['name']) && $block['blockName'] === 'madeitforms/input-field') {
                    $content = str_replace('name="'.$block['attrs']['name'].'"', 'name="'.$block['attrs']['name'].'" value="'.(isset($_POST[$block['attrs']['name']]) ? $_POST[$block['attrs']['name']] : (isset($_GET[$block['attrs']['name']]) ? $_GET[$block['attrs']['name']] : '')).'"', $content);
                } elseif (isset($block['attrs']['name']) && $block['blockName'] === 'madeitforms/largeinput-field') {
                    $content = str_replace('name="'.$block['attrs']['name'].'" required placeholder="'.($block['attrs']['placeholder'] ?? '').'">', 'name="'.$block['attrs']['name'].'" required placeholder="'.($block['attrs']['placeholder'] ?? '').'">'.(isset($_POST[$block['attrs']['name']]) ? $_POST[$block['attrs']['name']] : (isset($_GET[$block['attrs']['name']]) ? $_GET[$block['attrs']['name']] : '')), $content);
                    $content = str_replace('name="'.$block['attrs']['name'].'" placeholder="'.($block['attrs']['placeholder'] ?? '').'">', 'name="'.$block['attrs']['name'].'" placeholder="'.($block['attrs']['placeholder'] ?? '').'">'.(isset($_POST[$block['attrs']['name']]) ? $_POST[$block['attrs']['name']] : (isset($_GET[$block['attrs']['name']]) ? $_GET[$block['attrs']['name']] : 'TEST')), $content);

                    $content = str_replace('name="'.$block['attrs']['name'].'" required>', 'name="'.$block['attrs']['name'].'" required placeholder="'.($block['attrs']['placeholder'] ?? '').'">'.(isset($_POST[$block['attrs']['name']]) ? $_POST[$block['attrs']['name']] : (isset($_GET[$block['attrs']['name']]) ? $_GET[$block['attrs']['name']] : '')), $content);
                    $content = str_replace('name="'.$block['attrs']['name'].'">', 'name="'.$block['attrs']['name'].'" placeholder="'.($block['attrs']['placeholder'] ?? '').'">'.(isset($_POST[$block['attrs']['name']]) ? $_POST[$block['attrs']['name']] : (isset($_GET[$block['attrs']['name']]) ? $_GET[$block['attrs']['name']] : '')), $content);
                } elseif (isset($block['attrs']['name']) && $block['blockName'] === 'madeitforms/multi-value-field') {
                    foreach (explode("\n", $block['attrs']['values']) as $value) {
                        $content = str_replace('name="'.$block['attrs']['name'].'[]" value="'.$value.'"', 'name="'.$block['attrs']['name'].'[]" value="'.$value.'"'.(isset($_POST[$block['attrs']['name']]) && in_array($value, $_POST[$block['attrs']['name']]) ? ' checked' : (isset($_GET[$block['attrs']['name']]) && in_array($value, $_GET[$block['attrs']['name']]) ? ' checked' : '')), $content);
                    }
                }
            }

            if (isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
                $captchaCallback = 'onSubmit'.rand();
                $captchaErrorCallback = 'onErrorSubmit'.rand();

                $captcha = ' data-sitekey="'.$this->defaultSettings['reCaptcha']['key'].'" data-callback="'.$captchaCallback.'" data-error-callback="'.$captchaErrorCallback.'"';
                $formId = 'form_'.$this->form_id();
                $captcha_js = '<script>function '.$captchaCallback."(token) { submitMadeitForm('".$formId."'); }</script>";
                $captcha_js .= '<script>function '.$captchaErrorCallback.'(token) { }</script>';

                $content = str_replace('<button class="', '<button class="g-recaptcha ', $content);
                $content = str_replace('<button ', '<button '.$captcha, $content);
                $content .= $captcha_js;
            }

            $content = $this->checkQuiz($content);

            echo $content;
        }
        echo '</form>';
        $formHtml = ob_get_clean();

        echo apply_filters('madeit_forms_form_html', $formHtml, $id);
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
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $v = implode(', ', $v);
            }
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

    public function submitAjaxForm()
    {
        $id = $_POST['form_id'];

        $form = get_post($id);

        if ($form->post_type !== 'ma_forms') {
            echo json_encode(['success' => false, 'message' => __("Can't display the form.", 'forms-by-made-it')]);
            wp_die();
        }
        $this->form_id = $form->ID;

        if ($this->isSpam($_POST)) {
            $this->notifyError('Spam detected.');
            echo json_encode(['success' => false, 'message' => __('Spam detected.', 'forms-by-made-it')]);
            wp_die();
        }

        $error = false;
        $error_msg = '';
        $spamScore = null;
        if (isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
            $secretKey = $this->defaultSettings['reCaptcha']['secret'];
            if (!isset($_POST['g-recaptcha-response'])) {
                $error = true;
                $error_msg = isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", 'forms-by-made-it');
            }
            $response = $_POST['g-recaptcha-response'];
            $remoteIp = $_SERVER['REMOTE_ADDR'];
            $reCaptchaValidationUrl = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$response&remoteip=$remoteIp");
            $result = json_decode($reCaptchaValidationUrl, true);

            if ($this->defaultSettings['reCaptcha']['version'] === 'V3') {
                $spamScore = $result['score'] ?? null;
                if ($result['score'] < $this->defaultSettings['reCaptcha']['minScore']) {
                    $error = true;
                    $error_msg = isset($messages['check_captcha']) ? $messages['check_captcha'] : __('The spam filter suspects a problem. Contact us by phone or e-mail.', 'forms-by-made-it');
                }
            } else {
                if ($result['success'] != 1) {
                    $error = true;
                    $error_msg = isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", 'forms-by-made-it');
                }
            }
        }

        if (get_post_meta($form->ID, 'max_submits', true) == 1 && $this->hasAlreadyCompletedThisForm($form->ID)) {
            $error = true;
            $error_msg = isset($messages['already_submitted']) ? $messages['already_submitted'] : __('You have already submitted this form.', 'forms-by-made-it');
        }

        if ($error) {
            $this->notifyError($error_msg);
            echo json_encode(['success' => false, 'message' => $error_msg, 'spamscore' => $spamScore]);
            wp_die();
        }

        //validate input fields
        $error = false;
        $error_msg = '';
        $messages = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($form->ID, 'messages', true))), true);

        if (get_post_meta($form->ID, 'form_type', true) === 'html') {
            $formValue = get_post_meta($form->ID, 'form', true);
            $formValue = str_replace('\"', '"', $formValue);
            $formValue = str_replace("\'", "'", $formValue);
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
        } else {
            $blocks = parse_blocks($form->post_content);
            $blocks = $this->parseBlocks($blocks);

            foreach ($blocks as $block) {
                if (isset($block['attrs']['name'])) {
                    $tag = $block['attrs']['name'];
                    $tags[] = $tag;
                    $type = $block['attrs']['type'] ?? 'text';

                    if (isset($block['attrs']['required']) && $block['attrs']['required']) {
                        if (!isset($_POST[$tag]) || empty($_POST[$tag])) {
                            $error = true;
                            $error_msg = $messages['invalid_required'].' ('.$tag.')';
                        }
                    }

                    if (!empty($_POST[$tag]) && $type === 'email') {
                        if (!filter_var($_POST[$tag], FILTER_VALIDATE_EMAIL)) {
                            $error = true;
                            $error_msg = isset($messages['mod_text_invalid_email']) ? $messages['mod_text_invalid_email'] : $messages['validation_error'];
                        }
                    }

                    if (!empty($_POST[$tag]) && $type === 'url') {
                        if (!filter_var($_POST[$tag], FILTER_VALIDATE_URL)) {
                            $error = true;
                            $error_msg = isset($messages['mod_text_invalid_url']) ? $messages['mod_text_invalid_url'] : $messages['validation_error'];
                        }
                    }

                    if (!empty($_POST[$tag]) && $type === 'tel') {
                        if (!preg_match('%^[+]?[0-9()/ -]*$%', $_POST[$tag])) {
                            $error = true;
                            $error_msg = isset($messages['mod_text_invalid_phone']) ? $messages['mod_text_invalid_phone'] : $messages['validation_error'];
                        }
                    }

                    if (!empty($_POST[$tag]) && $type === 'number') {
                        if (!is_numeric($_POST[$tag])) {
                            $error = true;
                            $error_msg = isset($messages['mod_number_invalid_number']) ? $messages['mod_number_invalid_number'] : $messages['validation_error'];
                        }

                        if (isset($block['attrs']['minimum']) && $_POST[$tag] < $block['attrs']['minimum']) {
                            $error = true;
                            $error_msg = isset($messages['mod_number_number_too_small']) ? $messages['mod_number_number_too_small'] : $messages['validation_error'];
                        }

                        if (isset($block['attrs']['maximum']) && $_POST[$tag] > $block['attrs']['maximum']) {
                            $error = true;
                            $error_msg = isset($messages['mod_number_number_too_large']) ? $messages['mod_number_number_too_large'] : $messages['validation_error'];
                        }
                    }
                }
            }
        }

        if ($error) {
            $this->notifyError($error_msg);
            echo json_encode(['success' => false, 'message' => $error_msg]);
            wp_die();
        }

        //check spam
        $spam = false;

        //insert into DB
        $postData = $_POST;
        if ($spamScore !== null) {
            $postData['spamScore'] = $spamScore;
        }
        unset($postData['form_id']);
        unset($postData['action']);

        $inputId = -1;
        if (get_post_meta($form->ID, 'save_inputs', true) == 1) {
            $inputId = wp_insert_post([
                'post_title'  => 'Form submit '.$form->post_title.' - '.$this->getIP(),
                'post_status' => 'publish',
                'post_type'   => 'ma_form_inputs',
            ]);

            $postData = apply_filters('madeit_forms_post_data', $postData, $form->ID, $inputId);
            $postData = apply_filters('madeit_forms_'.$form->ID.'_post_data', $postData, $inputId);

            update_post_meta($inputId, 'form_id', $form->ID);
            update_post_meta($inputId, 'data', $this->enterToDB(json_encode($postData)));
            update_post_meta($inputId, 'ip', $this->getIP());
            update_post_meta($inputId, 'user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN');
            update_post_meta($inputId, 'spam', $spam ? 1 : 0);
            update_post_meta($inputId, 'read', 0);
            update_post_meta($inputId, 'result', '');
        }
        $outputHtml = '';

        $submittedTimes = isset($_COOKIE['madeit_form_'.$form->ID.'_submitted']) ? $_COOKIE['madeit_form_'.$form->ID.'_submitted'] : 0;
        setcookie('madeit_form_'.$form->ID.'_submitted', $submittedTimes, time() + 31556926);

        //execute actions
        $actions = json_decode(str_replace("\'", "'", $this->dbToEnter(get_post_meta($form->ID, 'actions', true))), true);
        if (count($actions) > 0) {
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
                    $data = apply_filters('madeit_forms_action_data', $data, $form->ID, $inputId, $actionInfo, $postData);
                    $result = call_user_func($action['callback'], $data, $messages, $actionInfo, $form->ID, $inputId, $postData);
                    if (is_array($result) && isset($result['type'])) {
                        if ($result['type'] == 'JS') {
                            $outputHtml .= '<script>'.$result['code'].'</script>';
                        } elseif ($result['type'] == 'HTML') {
                            $outputHtml .= str_replace('\"', '"', $result['code']);
                        }
                    } elseif ($result !== true) {
                        $error = true;
                        $error_msg = $result;
                    }
                }
            }
        }

        if ($error) {
            $this->notifyError($error_msg);
            echo json_encode(['success' => false, 'message' => $error_msg]);
            wp_die();
        } else {
            echo json_encode(['success' => true, 'message' => $messages['success'], 'html' => $outputHtml]);
            wp_die();
        }
    }

    public function generateViewImage()
    {
        if (isset($_GET['madeit_forms_view']) && $_GET['madeit_forms_view'] == 'yes' && isset($_GET['input_id'])) {
            $formInputId = $_GET['input_id'];
            $post = get_post($formInputId);
            if ($post->post_type === 'ma_form_inputs') {
                update_post_meta($post->ID, 'read', 1);
            }

            header('Content-Type: image/png');
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
            exit;
        }
    }

    public function addHooks()
    {
        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'generateViewImage'], 1);

        add_action('wp_ajax_madeit_forms_submit', [$this, 'submitAjaxForm']);
        add_action('wp_ajax_nopriv_madeit_forms_submit', [$this, 'submitAjaxForm']);
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

    private function isSpam($data)
    {
        $spam = false;

        //Check if IP is spam listed
        $spamIPs = apply_filters('madeit_forms_spam_ips', []);
        if (in_array($this->getIP(), $spamIPs)) {
            $spam = true;
        }

        //check user agent
        $spamUserAgents = apply_filters('madeit_forms_spam_user_agents', [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36 OPR/89.0.4447.51',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.99',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.106',
            'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:102.0) Gecko/20100101 Firefox/102.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.68',
        ]);
        if (isset($_SERVER['HTTP_USER_AGENT']) && in_array($_SERVER['HTTP_USER_AGENT'], $spamUserAgents)) {
            $spam = true;
        }

        //check words
        $spamWords = apply_filters('madeit_forms_spam_words', ['mail.ru']);
        foreach ($spamWords as $spamWord) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $v = implode(' ', $v);
                }

                if (stripos($v, $spamWord) !== false) {
                    $spam = true;
                    break;
                }
            }
        }

        return $spam;
    }

    private function hasAlreadyCompletedThisForm($formId)
    {
        $hasCompleted = false;

        if (isset($_COOKIE['madeit_form_'.$formId.'_submitted'])) {
            $hasCompleted = true;
        }

        return $hasCompleted;
    }

    private function checkQuiz($content)
    {
        if (strpos($content, 'wp-block-madeitforms-question-seperator') !== false) {
            //Is quiz
            $content = str_replace('wp-block-madeitforms-question-seperator madeit-forms-input-field', 'wp-block-madeitforms-question-seperator', $content);

            $contentSeperators = explode('<div class="wp-block-madeitforms-question-seperator"></div>', $content);

            $content = '';
            foreach ($contentSeperators as $key => $contentSeperator) {
                $content .= '<div class="madeit-forms-quiz-question '.($key > 0 ? 'hide-question' : '').'" data-question="'.$key.'">'.$contentSeperator;

                if (count($contentSeperators) - 1 != $key) {
                    $content .= '<div class="madeit-forms-quiz-question-buttons">';
                    if ($key > 0) {
                        $content .= '<button class="madeit-forms-quiz-question-button madeit-forms-quiz-question-button-prev" data-question="'.$key.'" '.($key == 0 ? 'disabled' : '').'>'.__('Previous', 'forms-by-made-it').'</button>';
                    }
                    $content .= '<button class="madeit-forms-quiz-question-button madeit-forms-quiz-question-button-next ms-auto" data-question="'.$key.'" '.($key == count($contentSeperators) - 1 ? 'disabled' : '').'>'.apply_filters('madeit_forms_question_button_next', __('Next', 'forms-by-made-it'), $key, $this->form_id).'</button>';
                    $content .= '</div>';
                }
                $content .= '</div>';
            }

            $content = '<div class="madeit-forms-quiz-container" data-steps="'.count($contentSeperators).'" data-current-step="0">'.$content.'</div>';
        }

        return $content;
    }

    private function notifyError($error = null)
    {
        global $_POST;
        //Send post request to url with all $_POST data, error, form_id, IP and user agent
        $url = 'https://portal.madeit.be/forms/error';

        $data = [
            'website' => get_site_url(),
            'error'   => $error,
            'form_id' => $this->form_id,
            'ip'      => $this->getIP(),
            'ua'      => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN',
            'data'    => json_encode($_POST, JSON_PRETTY_PRINT),
        ];

        error_log(json_encode($data, JSON_PRETTY_PRINT));

        //curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
    }
}
