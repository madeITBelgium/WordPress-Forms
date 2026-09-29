<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_GAAdsEvent extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('ga_ads_event_code', __('Google tag code', 'forms-by-made-it'), 'text', 'AW-');
        $this->addActionField('ga_ads_event_send_to', __('Google tag event code', 'forms-by-made-it'), 'text', '');

        $this->addAction('GA_ADS_EVENT', __('Google Ads Event', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $source = 'https://www.googletagmanager.com/gtag/js?id='.rawurlencode($data['ga_ads_event_code']);
        $code = wp_json_encode($data['ga_ads_event_code'], $flags);
        $conversion = wp_json_encode(['send_to' => $data['ga_ads_event_send_to']], $flags);

        return ['type' => 'HTML', 'code' => '<script async src="'.esc_url($source).'"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments)};
        gtag("js", new Date());
        gtag("config", '.$code.');
        gtag("event", "conversion", '.$conversion.');</script>'];
    }
}
