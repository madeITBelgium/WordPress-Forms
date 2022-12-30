<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Mailchimp extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('mc_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('mc_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('mc_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('mc_api_key', __('API Key', 'forms-by-made-it'), 'text', '');
        $this->addActionField('mc_list_id', __('List ID', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_mc_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('MAILCHIMP', __('Mailchimp', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $mc = null;

        try {
            $mc = new \DrewM\MailChimp\MailChimp($data['mc_api_key']); //your api key here
        } catch (Exception $e) {
            return 'You have not set an API key.';
        }

        $mergeFields = apply_filters('madeit_forms_mailchimp_merge_fields', [
            'FNAME' => $data['mc_firstname'],
            'LNAME' => $data['mc_name'],
        ], $data, $actionInfo);

        try {
            $d = $mc->post('lists/'.$data['mc_list_id'].'/members', [
                'email_address' => $data['mc_email'],
                'status'        => 'subscribed',
                'merge_fields'  => $mergeFields,
            ]);
        } catch (Exception $e) {
            if ($e->getMessage()) {
                return $e->getMessage();
            } else {
                return 'An unknown error occurred';
            }
        }

        return true;
    }
}
