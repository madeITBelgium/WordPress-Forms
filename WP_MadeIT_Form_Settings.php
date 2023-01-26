<?php

class WP_MadeIT_Form_Settings
{
    private $defaultSettings = [];

    public function __construct()
    {
        //$this->loadDefaultSettings();
    }

    public function loadDefaultSettings()
    {
        $this->defaultSettings = [
            'reCaptcha' => [
                'version'  => get_option('madeit_forms_reCaptcha_version', 'V2'),
                'enabled'  => get_option('madeit_forms_reCaptcha', false),
                'key'      => get_option('madeit_forms_reCaptcha_key', null),
                'secret'   => get_option('madeit_forms_reCaptcha_secret', null),
                'minScore' => get_option('madeit_forms_reCaptcha_minScore', 0.7),
            ],
        ];

        return $this->defaultSettings;
    }

    public function checkCheckbox($key)
    {
        if (isset($_POST[$key]) && $_POST[$key] == 1) {
            update_option($key, true);
        } else {
            update_option($key, false);
        }
    }

    public function checkTextbox($key)
    {
        if (isset($_POST[$key])) {
            update_option($key, $_POST[$key]);
        } else {
            update_option($key, '');
        }
    }
}
