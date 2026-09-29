<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_GAEvent extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('ga_event_category', __('Category', 'forms-by-made-it'), 'text', 'Forms');
        $this->addActionField('ga_event_action', __('Action', 'forms-by-made-it'), 'text', 'Submit');
        $this->addActionField('ga_event_label', __('Label', 'forms-by-made-it'), 'text', '[your-email]');

        $this->addAction('GA_EVENT', __('Google Analytics Event', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo, $formId = null, $inputId = null, $postData = null)
    {
        $arguments = wp_json_encode(['send', 'event', $data['ga_event_category'], $data['ga_event_action'], $data['ga_event_label']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return ['type' => 'JS', 'code' => 'window.onload = function () { ga.apply(null, '.$arguments.'); }'];
    }
}
