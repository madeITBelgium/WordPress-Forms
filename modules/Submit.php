<?php
class WP_MADEIT_FORM_Module_Submit
{
    private $tags = array();
    private $message_fields = array('submit' => array());
    private $settings;
    private $defaultSettings = [];
    
    public function __construct($wp_madeit_form_settings) {
        $this->settings = $wp_madeit_form_settings;
        $this->defaultSettings = $wp_madeit_form_settings->loadDefaultSettings();
        
        $this->addTag('submit', __('Submit', 'forms-by-made-it'), 'tag_generator_submit', array($this, 'tag_generator_submit'), array($this, 'validation_submit'));
        $this->addMessageField('submit', 'check_captcha', __('The captcha failed the validation.', 'forms-by-made-it'), __("The captcha couldn't validate you.", "forms-by-made-it"));
        
        $this->addHooks();
    }
    
    private function addTag($name, $title, $content, $form, $validation) {
        $this->tags[$name] = array('title' => $title, 'content' => $content, 'form' => $form, 'validation' => $validation);
    }
        
    private function addMessageField($tag, $name, $label, $value = "") {
        $this->message_fields[$tag][] = array('field' => $name, 'description' => $label, 'value' => $value);
    }
    
    public function getAction($actions) {
        $ar = array();
        foreach($this->tags as $key => $tag) {
            $ar[$key] = $tag;
            $ar[$key]['message_fields'] = isset($this->message_fields[$key]) && is_array($this->message_fields[$key]) ? $this->message_fields[$key] : array();
            $ar[$key]['options'] = $this->tag_generator_options();
        }
        return array_merge($actions, $ar);
    }
    
    public function tag_generator_options() {
        return [
            'label' => [
                'text' => __('Label', 'forms-by-made-it'),
                'type' => 'text',
            ],
            'id' => [
                'text' => __('Id attribute', 'forms-by-made-it'),
                'type' => 'text',
            ],
            'class' => [
                'text' => __('Class attribute', 'forms-by-made-it'),
                'type' => 'text',
            ],
        ];
    }
    
    public function tag_generator_submit($contact_form, $args = '') {
        $args = wp_parse_args( $args, array() );
        $type = $args['id'];

        $description = __("Generate a form-tag for a submit button. For more details, see %s.", 'forms-by-made-it');
        $desc_link = '<a href="' . esc_url('https://www.madeit.be/wordpress/forms/docs/submit-button/') . '" target="_blank">' . __('Text Fields', 'forms-by-made-it') . '</a>';

        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo sprintf(esc_html($description), $desc_link); ?></legend>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><?php echo esc_html(__('Label', 'forms-by-made-it')); ?></label></th>
                            <td>
                                <input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php echo esc_html(__('Class attribute', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>
        <div class="insert-box">
            <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'forms-by-made-it')); ?>" />
            </div>
        </div>
        <?php
    }
    
    public function validation_submit($tagOptions, $value, $messages) {
        if(isset($tagOptions['ajax']) && $tagOptions['ajax']) {
            return true;
        }
        
        if(isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
            $secretKey = $this->defaultSettings['reCaptcha']['secret'];
            if(!isset($_POST['g-recaptcha-response'])) {
                return isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", "forms-by-made-it");
            }
            $response = $_POST['g-recaptcha-response'];     
            $remoteIp = $_SERVER['REMOTE_ADDR'];
            $reCaptchaValidationUrl = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$response&remoteip=$remoteIp");
            $result = json_decode($reCaptchaValidationUrl, TRUE);
            return $result['success'] == 1 ? true : (isset($messages['check_captcha']) ? $messages['check_captcha'] : __("The captcha couldn't validate you.", "forms-by-made-it"));
        }
        return true;
    }
    
    public function submitShortcode($atts) {
        extract(shortcode_atts(array(
            'name' => "",
            'id' => '',
            'class' => '',
            'value' => '',
        ), $atts ));
        ob_start();
        $captcha = "";
        $captcha_js = "";
        if(isset($this->defaultSettings['reCaptcha']['enabled']) && $this->defaultSettings['reCaptcha']['enabled']) {
            $captchaCallback = "onSubmit" . rand();
            $captchaErrorCallback = "onErrorSubmit" . rand();
            $class .= ' g-recaptcha';
            $captcha = ' data-sitekey="' . $this->defaultSettings['reCaptcha']['key'] . '" data-callback="' . $captchaCallback . '" data-error-callback="' . $captchaErrorCallback . '"';
            $formId = "form_" . apply_filters('madeit_forms_form_id', "");
            $captcha_js = "<script>function " . $captchaCallback . "(token) { submitMadeitForm('" . $formId . "'); }</script>";
            $captcha_js .= "<script>function " . $captchaErrorCallback . "(token) { }</script>";
        }
        ?>
        <input type="submit" name="btn_submit"
           <?php if($captcha != "") { echo $captcha; } ?>
           <?php if($value != "") { ?> value="<?php echo esc_html($value); ?>" <?php } ?>
           <?php if($id != "") { ?> id="<?php echo esc_html($id); ?>" <?php } ?>
           class="<?php echo esc_html( apply_filters('madeit_forms_module_class', $class, 'submit') ); ?>"
               >
        <?php
        if($captcha_js != "") {
            echo $captcha_js;
        }
        $content = ob_get_clean();
        return $content;
    }

    public function addHooks() {
        add_filter('madeit_forms_modules', array($this, 'getAction'));
        
        
        add_shortcode('submit', [$this, 'submitShortcode']);
    }
}