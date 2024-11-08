<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_ActiveCampaign extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('ac_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('ac_firstname', __('Firstname', 'forms-by-made-it'), 'text', '[your-firstname]');
        $this->addActionField('ac_name', __('Name', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('ac_api_url', __('API URL', 'forms-by-made-it'), 'text', 'https://xxxx.api-us1.com');
        $this->addActionField('ac_api_key', __('API Key', 'forms-by-made-it'), 'text', '');
        $this->addActionField('ac_list_id', __('List ID', 'forms-by-made-it'), 'text', '');

        $this->addMessageField('action_ac_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('ACTIVECAMPAIGN', __('ActiveCampaign', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $apiInstance = null;
        $createContact = null;

        $array = [
            'email'       => $data['ac_email'],
            'firstName'   => $data['ac_firstname'],
            'lastName'    => $data['ac_name'],
            'fieldValues' => [],
        ];
        //if ac_list_id contains , then it is a list of lists
        if (!empty($data['ac_list_id'])) {
            if (strpos($data['ac_list_id'], ',') !== false) {
                $lists = explode(',', $data['ac_list_id']);

                $stndArray = $array;
                foreach ($lists as $list) {
                    $array = $stndArray;
                    $array['p['.$list.']'] = $list;
                    $array['status['.$list.']'] = 1;

                    $attributes = apply_filters('madeit_forms_activecampaign_attributes', $array, $data, $actionInfo, $formId, $postData);

                    $url = $data['ac_api_url'].'/api/3/contact/sync';
                    $body = ['contact' => $attributes];
                    list($response, $statusCode) = $this->requestAC('POST', $url, $data['ac_api_key'], $body);

                    $response = json_decode($response, true);
                    $error = false;
                    $contactId = null;
                    if (isset($response['errors'])) {
                        $error = $messages['errors'][0]['title'];

                        if (isset($response['errors'][0]['code']) && $response['errors'][0]['code'] === 'duplicate') {
                            //contact already exists

                            //Search contact
                            $url = $data['ac_api_url'].'/api/3/contacts?email='.$data['ac_email'];
                            list($response, $statusCode) = $this->requestAC('GET', $url, $data['ac_api_key']);
                            if ($statusCode == 200) {
                                $response = json_decode($response, true);
                                if (isset($response['contacts']) && count($response['contacts']) > 0) {
                                    $contactId = $response['contacts'][0]['id'];

                                    $body = [
                                        'contactList' => [
                                            'contact' => $contactId,
                                            'list'    => $list,
                                            'status'  => 1,
                                        ],
                                    ];
                                    $this->requestAC('POST', $data['ac_api_url'].'/api/3/contactLists', $data['ac_api_key'], $body);
                                }
                            }
                        }
                    }

                    if ($error) {
                        return $error;
                    }

                    $contactId = $response['contact']['id'];
                    $url = $data['ac_api_url'].'/api/3/contactLists';
                    $body = ['contactList' => ['contact' => $contactId, 'list' => $list, 'status' => 1]];
                    list($response, $statusCode) = $this->requestAC('POST', $url, $data['ac_api_key'], $body);

                    update_post_meta($inputId, 'ac_contact_id', $contactId);

                    if ($statusCode != 201 && $statusCode !== 200) {
                        return 'An unknown error occurred';
                    }
                }
            } else {
                $array['p['.$data['ac_list_id'].']'] = $data['ac_list_id'];
                $array['status['.$data['ac_list_id'].']'] = 1;

                $attributes = apply_filters('madeit_forms_activecampaign_attributes', $array, $data, $actionInfo, $formId, $postData);

                $url = $data['ac_api_url'].'/api/3/contact/sync';
                $body = ['contact' => $attributes];
                list($response, $statusCode) = $this->requestAC('POST', $url, $data['ac_api_key'], $body);

                $response = json_decode($response, true);
                $error = false;
                $contactId = null;
                if (isset($response['errors'])) {
                    $error = $messages['errors'][0]['title'];

                    if (isset($response['errors'][0]['code']) && $response['errors'][0]['code'] === 'duplicate') {
                        //contact already exists

                        //Search contact
                        $url = $data['ac_api_url'].'/api/3/contacts?email='.$data['ac_email'];
                        list($response, $statusCode) = $this->requestAC('GET', $url, $data['ac_api_key']);
                        if ($statusCode == 200) {
                            $response = json_decode($response, true);
                            if (isset($response['contacts']) && count($response['contacts']) > 0) {
                                $contactId = $response['contacts'][0]['id'];

                                $contactId = $response['contacts'][0]['id'];

                                $body = [
                                    'contactList' => [
                                        'contact' => $contactId,
                                        'list'    => $data['ac_list_id'],
                                        'status'  => 1,
                                    ],
                                ];
                                $this->requestAC('POST', $data['ac_api_url'].'/api/3/contactLists', $data['ac_api_key'], $body);
                            }
                        }
                    }
                }

                if ($error) {
                    return $error;
                }

                $contactId = $response['contact']['id'];
                $url = $data['ac_api_url'].'/api/3/contactLists';
                $body = ['contactList' => ['contact' => $contactId, 'list' => $data['ac_list_id'], 'status' => 1]];
                list($response, $statusCode) = $this->requestAC('POST', $url, $data['ac_api_key'], $body);

                update_post_meta($inputId, 'ac_contact_id', $contactId);

                if ($statusCode != 201 && $statusCode !== 200) {
                    return 'An unknown error occurred';
                }
            }
        }

        return true;
    }

    private function requestAC($type, $url, $apitoken, $data = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Token: '.$apitoken,
            'Accept: application/json',
            'Content-Type: application/json',
        ]);
        if ($type == 'POST' || $type == 'PUT' || $type == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$response, $statusCode];
    }
}
