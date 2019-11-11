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

    public function callback($data, $messages)
    {
        $mc = null;

        try {
            $mc = new Mailchimp($data['mc_api_key']); //your api key here
        } catch (Mailchimp_Error $e) {
            return 'You have not set an API key.';
        }

        try {
            $d = $mc->lists->subscribe($data['mc_list_id'], [
                'email'        => $data['mc_email'],
                'merge_fields' => ['fname' => $data['mc_firstname'], 'lname' => $data['mc_name']],
            ]);

            //print_r($d);exit;
        } catch (Mailchimp_Error $e) {
            if ($e->getMessage()) {
                return $e->getMessage();
            } else {
                return 'An unknown error occurred';
            }
        }

        return true;
    }
}
