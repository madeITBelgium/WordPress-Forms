<?php
class WP_MadeIT_Form_Settings {
    private $defaultSettings = array();

    function __construct() {
        $this->loadDefaultSettings();
    }
    
    public function loadDefaultSettings() {
        if(get_option('madeit_forms_reCaptcha', null) == null) {
            update_option('madeit_forms_reCaptcha', false);
        }
        if(get_option('madeit_forms_reCaptcha_key', null) == null) {
            update_option('madeit_forms_reCaptcha_key', null);
        }
        if(get_option('madeit_forms_reCaptcha_secret', null) == null) {
            update_option('madeit_forms_reCaptcha_secret', null);
        }
        
        $this->defaultSettings = [
            'reCaptcha' => [
                'enabled' => get_option('madeit_forms_reCaptcha', false),
                'key' => get_option('madeit_forms_reCaptcha_key', null),
                'secret' => get_option('madeit_forms_reCaptcha_secret', null),
            ],
        ];
        return $this->defaultSettings;
    }
    
    public function checkCheckbox($key) {
        if(isset($_POST[$key]) && $_POST[$key] == 1) {
            update_option($key, true);
        }
        else {
            update_option($key, false);
        }
    }
    
    public function checkTextbox($key) {
        if(isset($_POST[$key])) {
            update_option($key, $_POST[$key]);
        }
        else {
            update_option($key, "");
        }
    }
}