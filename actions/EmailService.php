<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_EmailService extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('es_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('es_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('es_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        //$this->addActionField('es_api_key', __('API Key', 'forms-by-made-it'), 'text', '');
        $this->addActionField('es_list_id', __('List ID', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_es_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));
        $this->addMessageField('action_es_invalid_email', __('Invalid emailadres', 'forms-by-made-it'), __('The email address entered seems incorrect.', 'forms-by-made-it'));

        $this->addAction('EMAILSERVICEBE', __('Email-service.be', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $mergeFields = apply_filters('madeit_forms_emailservice_merge_fields', [
            'email'      => $data['es_email'],
            'first_name' => $data['es_firstname'],
            'last_name'  => $data['es_name'],
        ], $data, $actionInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.email-service.be/api/1.0/subscribe/'.$data['es_list_id']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($mergeFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $server_output = curl_exec($ch);
        curl_close($ch);

        if ($httpcode === 200) {
            return true;
        } elseif ($httpcode === 422) {
            return $messages['action_es_invalid_email'];
        }

        return true;
    }
}
