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

        $body = $this->buildBody($rawBody, $actionInfo, $postData);
        $body = apply_filters('madeit_forms_webhook_body', $body, $data, $messages, $actionInfo, $formId, $inputId, $postData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data['wh_url']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $data['wh_type']);
        $requestHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if (!empty($data['wh_headers'])) {
            $headers = explode("\n", $data['wh_headers']);
            foreach ($headers as $header) {
                $header = trim($header);
                if (!empty($header)) {
                    $requestHeaders[] = $header;
                }
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        if (is_array($body) && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($body));
        } elseif (is_string($body) && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $server_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200) {
            return true;
        } elseif ($httpcode === 400) {
            return $messages['action_wh_error'];
        }

        return true;
    }

    private function buildBody($rawBody, $actionInfo, $postData)
    {
        if (is_string($rawBody)) {
            $decodedBody = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBody)) {
                return $decodedBody;
            }
        }

        if (!is_array($postData) || !isset($actionInfo['wh_body']) || !is_string($actionInfo['wh_body'])) {
            return [];
        }

        $templateBody = json_decode($actionInfo['wh_body'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($templateBody)) {
            return [];
        }

        return $this->replacePlaceholders($templateBody, $postData);
    }

    private function replacePlaceholders($value, $postData)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->replacePlaceholders($item, $postData);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($postData) {
            $tag = $matches[1];
            if (!array_key_exists($tag, $postData)) {
                return $matches[0];
            }

            $replacement = $postData[$tag];
            if (is_array($replacement)) {
                return implode(', ', $replacement);
            }

            if (is_scalar($replacement) || $replacement === null) {
                return (string) $replacement;
            }

            return '';
        }, $value);
    }
}
