<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_Javacript extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('js_event_code', __('Code', 'forms-by-made-it'), 'textarea', 'Forms');

        $this->addAction('JS_EVENT', __('HTML/Javascript Event', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages)
    {
        return ['type' => 'HTML', 'code' => $data['js_event_code']];
    }
}
