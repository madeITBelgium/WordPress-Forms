<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_Mailpoet extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('mp_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('mp_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('mp_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('mp_list_id', __('List ID', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_mp_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('MAILPOET', __('Mailpoet', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages)
    {
        $listIDs = !empty($data['mp_list_id']) ? [$data['mp_list_id']] : [];

        //in this array firstname and lastname are optional
        $user_data = [
            'email'     => !empty($data['mp_email']) ? $data['mp_email'] : '',
            'firstname' => !empty($data['mp_firstname']) ? $data['mp_firstname'] : '',
            'lastname'  => !empty($data['mp_name']) ? $data['mp_name'] : '',
        ];

        if (count($listIDs) > 0) {
            $data_subscriber = ['user' => $user_data, 'user_list' => ['list_ids' => $listIDs]];
        } else {
            $data_subscriber = ['user' => $user_data];
        }

        try {
            $helper_user = WYSIJA::get('user', 'helper');
            $helper_user->addSubscriber($data_subscriber);
        } catch (Exception $e) {
        }

        return true;
    }
}
