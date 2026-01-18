<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Webhook extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('wh_type', __('Type', 'forms-by-made-it'), 'text', 'POST');
        $this->addActionField('wh_url', __('URL', 'forms-by-made-it'), 'text', 'https://example.com/webhook');
        $this->addActionField('wh_headers', __('Headers', 'forms-by-made-it'), 'textarea', '');
        $this->addActionField('wh_body', __('Body', 'forms-by-made-it'), 'textarea', "{\n    \"email\": \"[your-email]\",\n    \"name\": \"[your-name]\"\n}");

        $this->addMessageField('action_wh_error', __('Can not subscribe', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('WEBHOOK', __('Webhook', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $rawBody = apply_filters('madeit_forms_webhook_raw_body', $data['wh_body'], $data, $messages, $actionInfo, $formId, $inputId, $postData);

        //check json_decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            //Can we fix the error?
            if (json_last_error() === JSON_ERROR_SYNTAX) {
                $body = str_replace(['\n', '\r'], '', $rawBody);
                $body = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON decode error: '.json_last_error_msg());
                    error_log('Raw body: '.$rawBody);
                } else {
                    $rawBody = json_encode($body);
                }
            } else {
                error_log('JSON decode error: '.json_last_error_msg());
                error_log('Raw body: '.$rawBody);
            }
        }

        //check if $rawBody is a valid JSON string
        if (is_string($rawBody) && is_array(json_decode($rawBody, true))) {
            $body = json_decode($rawBody, true);
            $body = apply_filters('madeit_forms_webhook_body', $rawBody, $data, $messages, $actionInfo, $formId, $inputId, $postData);
        } else {
            $body = $rawBody;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data['wh_url']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $data['wh_type']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        if (!empty($data['wh_headers'])) {
            $headers = explode("\n", $data['wh_headers']);
            foreach ($headers as $header) {
                $header = trim($header);
                if (!empty($header)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [$header]);
                }
            }
        }

        if (!empty($body)) {
            if (is_array($body)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $server_output = curl_exec($ch);
        curl_close($ch);

        if ($httpcode === 200) {
            return true;
        } elseif ($httpcode === 400) {
            return $messages['action_wh_error'];
        }

        return true;
    }
}
