<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_Redirect extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('redirect_url', __('URL', 'forms-by-made-it'), 'text', '');

        $this->addAction('REDIRECT', __('Redirect to specific URL', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $redirectUrl = apply_filters('madeit_forms_redirect_url', $actionInfo['redirect_url'], $data, $messages, $actionInfo, $formId, $inputId, $postData);

        return ['type' => 'HTML', 'code' => '<script>window.location.href="'.$redirectUrl.'";</script>'];
    }
}
