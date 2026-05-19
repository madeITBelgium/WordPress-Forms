<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Odoo extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('odoo_url', __('Odoo URL', 'forms-by-made-it'), 'text', 'https://example.odoo.com');
        $this->addActionField('odoo_db', __('Database', 'forms-by-made-it'), 'text', '');
        $this->addActionField('odoo_username', __('Username', 'forms-by-made-it'), 'text', '');
        $this->addActionField('odoo_api_key', __('API Key', 'forms-by-made-it'), 'text', '');
        $this->addActionField('odoo_name', __('Lead title', 'forms-by-made-it'), 'text', '[your-name]');
        $this->addActionField('odoo_email', __('Email', 'forms-by-made-it'), 'text', '[your-email]');
        $this->addActionField('odoo_phone', __('Phone', 'forms-by-made-it'), 'text', '[your-phone]');
        $this->addActionField('odoo_company', __('Company', 'forms-by-made-it'), 'text', '[your-company]');
        $this->addActionField('odoo_values', __('Extra lead values (JSON)', 'forms-by-made-it'), 'textarea', "{\n    \"description\": \"New lead from form [form_id] (submit [input_id])\"\n}");

        $this->addMessageField('action_odoo_error', __('Can not create lead', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('ODOO', __('Odoo', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $errorMessage = isset($messages['action_odoo_error']) ? $messages['action_odoo_error'] : __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it');

        $url = isset($data['odoo_url']) ? trim((string) $data['odoo_url']) : '';
        $database = isset($data['odoo_db']) ? trim((string) $data['odoo_db']) : '';
        $username = isset($data['odoo_username']) ? trim((string) $data['odoo_username']) : '';
        $apiKey = isset($data['odoo_api_key']) ? trim((string) $data['odoo_api_key']) : '';

        if ($url === '' || $database === '' || $username === '' || $apiKey === '') {
            return $errorMessage;
        }

        $values = $this->buildLeadValues($data, $actionInfo, $postData, $formId, $inputId);
        if (empty($values['name']) && !empty($values['email_from'])) {
            $values['name'] = $values['email_from'];
        }
        if (empty($values['name'])) {
            $values['name'] = 'Website lead';
        }

        $values = apply_filters('madeit_forms_odoo_lead_values', $values, $data, $messages, $actionInfo, $formId, $inputId, $postData);

        $uid = $this->authenticate($url, $database, $username, $apiKey);
        if (!$uid) {
            return $errorMessage;
        }

        $leadId = $this->createLead($url, $database, $uid, $apiKey, $values);
        if (!$leadId) {
            return $errorMessage;
        }

        if (!empty($inputId)) {
            update_post_meta($inputId, 'odoo_lead_id', $leadId);
        }

        return true;
    }

    private function authenticate($url, $database, $username, $apiKey)
    {
        $result = $this->requestJsonRpc($url, [
            'service' => 'common',
            'method'  => 'authenticate',
            'args'    => [$database, $username, $apiKey, []],
        ]);

        if ($result === false || !is_numeric($result) || (int) $result <= 0) {
            return false;
        }

        return (int) $result;
    }

    private function createLead($url, $database, $uid, $apiKey, $values)
    {
        $result = $this->requestJsonRpc($url, [
            'service' => 'object',
            'method'  => 'execute_kw',
            'args'    => [$database, (int) $uid, $apiKey, 'crm.lead', 'create', [$values]],
        ]);

        if ($result === false || !is_numeric($result) || (int) $result <= 0) {
            return false;
        }

        return (int) $result;
    }

    private function requestJsonRpc($url, $params)
    {
        $endpoint = rtrim($url, '/').'/jsonrpc';
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'params'  => $params,
            'id'      => wp_rand(1, 999999),
        ];

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpcode < 200 || $httpcode >= 300) {
            return false;
        }

        $body = json_decode($response, true);
        if (!is_array($body)) {
            return false;
        }

        if (!empty($body['error'])) {
            return false;
        }

        return isset($body['result']) ? $body['result'] : false;
    }

    private function buildLeadValues($data, $actionInfo, $postData, $formId, $inputId)
    {
        $baseValues = [
            'name'         => $this->replaceStringPlaceholders(isset($data['odoo_name']) ? (string) $data['odoo_name'] : '', $postData, $formId, $inputId),
            'email_from'   => $this->replaceStringPlaceholders(isset($data['odoo_email']) ? (string) $data['odoo_email'] : '', $postData, $formId, $inputId),
            'phone'        => $this->replaceStringPlaceholders(isset($data['odoo_phone']) ? (string) $data['odoo_phone'] : '', $postData, $formId, $inputId),
            'partner_name' => $this->replaceStringPlaceholders(isset($data['odoo_company']) ? (string) $data['odoo_company'] : '', $postData, $formId, $inputId),
        ];

        $jsonValues = [];
        $rawValues = isset($actionInfo['odoo_values']) ? $actionInfo['odoo_values'] : '';
        if (is_string($rawValues) && trim($rawValues) !== '') {
            $decoded = json_decode($rawValues, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $jsonValues = $this->replacePlaceholders($decoded, $postData, $formId, $inputId);
            }
        }

        $values = array_merge($baseValues, $jsonValues);

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                unset($values[$key]);
            }
        }

        return $values;
    }

    private function replacePlaceholders($value, $postData, $formId, $inputId)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->replacePlaceholders($item, $postData, $formId, $inputId);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->replaceStringPlaceholders($value, $postData, $formId, $inputId);
    }

    private function replaceStringPlaceholders($value, $postData, $formId, $inputId)
    {
        $replacements = [
            'form_id'  => $formId,
            'input_id' => $inputId,
        ];

        if (is_array($postData)) {
            foreach ($postData as $key => $item) {
                if (is_array($item)) {
                    $replacements[$key] = implode(', ', $item);
                    continue;
                }

                if (is_scalar($item) || $item === null) {
                    $replacements[$key] = (string) $item;
                }
            }
        }

        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($replacements) {
            $tag = $matches[1];
            if (!array_key_exists($tag, $replacements)) {
                return $matches[0];
            }

            return (string) $replacements[$tag];
        }, $value);
    }
}
