<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Klaviyo extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('kly_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('kly_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('kly_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('kly_company_id', __('Company ID', 'forms-by-made-it'), 'text', '');
        $this->addActionField('kly_list_id', __('List ID', 'forms-by-made-it'), 'text', '');
        $this->addActionField('kly_custom_source', __('Custom Source', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_kly_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));
        $this->addMessageField('action_kly_invalid_email', __('Invalid emailadres', 'forms-by-made-it'), __('The email address entered seems incorrect.', 'forms-by-made-it'));

        $this->addAction('MAILERLITE', __('MailerLite', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo)
    {
        $mergeFields = apply_filters('madeit_forms_klaviyo_merge_fields', [
            'type'       => 'subscription',
            'attributes' => [
                'properties' => [
                    'name' => trim($data['kly_firstname'].' '.$data['kly_name']),
                ],
            ],
            'email'         => $data['kly_email'],
            'list_id'       => $data['kly_list_id'],
            'custom_source' => $data['kly_custom_source'],
        ], $data, $actionInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://a.klaviyo.com/client/subscriptions/?company_id='.$data['kly_company_id']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'revision: 2022-10-17']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => $mergeFields]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $server_output = curl_exec($ch);
        curl_close($ch);

        if ($httpcode === 202) {
            return true;
        } elseif ($httpcode === 400) {
            return $messages['action_ml_error'];
        }

        return true;
    }
}
