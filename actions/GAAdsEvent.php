<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_GAAdsEvent extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('ga_ads_event_code', __('Google tag code', 'forms-by-made-it'), 'text', 'AW-');

        $this->addAction('GA_ADS_EVENT', __('Google Ads Event', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo)
    {
        return ['type' => 'HTML', 'code' => '<script async src="https://www.googletagmanager.com/gtag/js?id='.$data['ga_ads_event_code']."\"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments)};
        gtag('js', new Date());
        gtag('config', '".$data['ga_ads_event_code']."');</script>"];
    }
}
