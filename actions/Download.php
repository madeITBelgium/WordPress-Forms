<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_Download extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('download_url', __('Download URL', 'forms-by-made-it'), 'text', '');

        $this->addAction('DOWNLOAD', __('File download', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $url = esc_url_raw($data['download_url'], ['http', 'https']);

        return ['type' => 'HTML', 'code' => '<script>window.open('.wp_json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).', "_blank", "noopener");</script>'];
    }
}
