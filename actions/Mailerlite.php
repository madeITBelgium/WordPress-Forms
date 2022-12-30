<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Mailerlite extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('ml_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('ml_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('ml_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('ml_api_key', __('API Key', 'forms-by-made-it'), 'text', '');
        //$this->addActionField('ml_list_id', __('List ID', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_ml_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));
        $this->addMessageField('action_ml_invalid_email', __('Invalid emailadres', 'forms-by-made-it'), __('The email address entered seems incorrect.', 'forms-by-made-it'));

        $this->addAction('MAILERLITE', __('MailerLite', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $mergeFields = apply_filters('madeit_forms_mailerlite_merge_fields', [
            'email'       => $data['ml_email'],
            'name'        => trim($data['ml_firstname'].' '.$data['ml_name']),
            'resubscribe' => true,
            'signup_ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ], $data, $actionInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailerlite.com/api/v2/subscribers');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-MailerLite-ApiKey: '.$data['ml_api_key'],
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($mergeFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $server_output = curl_exec($ch);
        curl_close($ch);

        if ($httpcode === 200) {
            return true;
        } elseif ($httpcode === 400) {
            return $messages['action_ml_error'];
        }

        return true;
    }
}
