<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}

class WP_MADEIT_FORM_Nizu extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('nizu_url', __('Nizu form URL', 'forms-by-made-it'), 'text', 'https://yourcompany.nizu.be/collect_leads/save');
        $this->addActionField('nizu_company_name', __('Company name', 'forms-by-made-it'), 'text', '[company_name]');
        $this->addActionField('nizu_first_name', __('First name', 'forms-by-made-it'), 'text', '[first_name]');
        $this->addActionField('nizu_last_name', __('Last name', 'forms-by-made-it'), 'text', '[last_name]');
        $this->addActionField('nizu_email', __('Email', 'forms-by-made-it'), 'text', '[email]');
        $this->addActionField('nizu_lead_source_id', __('Lead source ID', 'forms-by-made-it'), 'text', '6');
        $this->addActionField('nizu_lead_owner_id', __('Lead owner ID', 'forms-by-made-it'), 'text', '1');

        $this->addMessageField('action_nizu_error', __('Can not submit to Nizu', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('NIZU', __('Nizu.be', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $errorMessage = $messages['action_nizu_error'] ?? __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it');
        $url = isset($data['nizu_url']) ? trim((string) $data['nizu_url']) : '';
        if ($url === '') {
            return $errorMessage;
        }

        $fields = [
            'company_name'   => 'nizu_company_name',
            'first_name'     => 'nizu_first_name',
            'last_name'      => 'nizu_last_name',
            'email'          => 'nizu_email',
            'lead_source_id' => 'nizu_lead_source_id',
            'lead_owner_id'  => 'nizu_lead_owner_id',
        ];
        $body = [];
        foreach ($fields as $field => $setting) {
            $value = isset($data[$setting]) ? (string) $data[$setting] : '';
            $body[$field] = $this->replacePlaceholders($value, $postData, $formId, $inputId);
        }

        $response = wp_safe_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept'       => 'text/html,application/xhtml+xml',
            ],
            'body'                => $body,
            'timeout'             => 30,
            'redirection'         => 0,
            'sslverify'           => true,
            'limit_response_size' => 65536,
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) {
            return $errorMessage;
        }

        return true;
    }

    private function replacePlaceholders($value, $postData, $formId, $inputId)
    {
        $replacements = [
            'form_id'  => $formId,
            'input_id' => $inputId,
        ];

        if (is_array($postData)) {
            foreach ($postData as $key => $item) {
                if (is_array($item)) {
                    $replacements[$key] = implode(', ', $item);
                } elseif (is_scalar($item) || $item === null) {
                    $replacements[$key] = (string) $item;
                }
            }
        }

        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($replacements) {
            if (!array_key_exists($matches[1], $replacements)) {
                return $matches[0];
            }

            return (string) $replacements[$matches[1]];
        }, $value);
    }
}
