<?php

class WP_MADEIT_FORM_Action
{
    public $action_fields = [];
    public $message_fields = [];
    public $actions = [];

    public function __construct()
    {
        $this->addHooks();
    }

    public function addActionField($name, $label, $type, $value = '', $options = [])
    {
        $this->action_fields[$name] = ['label'      => $label,
                                          'type'    => $type,
                                          'value'   => $value,
                                          'options' => $options, ];
    }

    public function addMessageField($name, $label, $value = '')
    {
        $this->message_fields[] = ['field' => $name, 'description' => $label, 'value' => $value];
    }

    public function addAction($key, $title, $callback)
    {
        $this->actions[$key] = ['title' => $title, 'action_fields' => $this->action_fields, 'message_fields' => $this->message_fields, 'callback' => $callback];
    }

    public function getAction($actions)
    {
        return array_merge($actions, $this->actions);
    }

    public function callback($data, $messages)
    {
        return $messages['failed'];
    }

    public function addHooks()
    {
        add_filter('madeit_forms_actions', [$this, 'getAction']);
    }
}
