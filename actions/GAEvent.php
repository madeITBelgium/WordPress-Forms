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

    public function callback($data, $messages)
    {
        return ['type' => 'JS', 'code' => "ga('send', 'event', '".$data['ga_event_category']."', '".$data['ga_event_action']."', '".$data['ga_event_label']."');"];
    }
}
