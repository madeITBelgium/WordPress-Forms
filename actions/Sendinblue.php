<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Sendinblue extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('sib_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('sib_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('sib_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('sib_api_key', __('API Key', 'forms-by-made-it'), 'text', '');
        $this->addActionField('sib_list_id', __('List ID', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_sib_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('SENDINBLUE', __('Send in blue', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo)
    {
        $apiInstance = null;
        $createContact = null;

        try {
            // Configure API key authorization: api-key
            $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $data['sib_api_key']);
            $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $config);
        } catch (Exception $e) {
            return 'You have not set an API key.';
        }

        $attributes = apply_filters('madeit_forms_sendinblue_attributes', [
            'FNAME' => $data['sib_firstname'],
            'LNAME' => $data['sib_name'],
        ], $data, $actionInfo);

        try {
            $createContact = new \SendinBlue\Client\Model\CreateContact([
                'email'      => $data['sib_email'],
                'attributes' => $attributes,
                'listIds'    => [$data['sib_list_id']],
            ]);
            $result = $apiInstance->createContact($createContact);
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
