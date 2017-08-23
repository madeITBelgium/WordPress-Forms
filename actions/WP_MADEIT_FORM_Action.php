<?php
class WP_MADEIT_FORM_Action
{
    public $action_fields = array();
    public $message_fields = array();
    public $actions = array();
    
    public function __construct() {
        $this->addHooks();
    }
    
    public function addActionField($name, $label, $type, $value = "", $options = array()) {
        $this->action_fields[$name] = array('label' => $label,
                                          'type' => $type,
                                          'value' => $value,
                                          'options' => $options);
    }
    
    public function addMessageField($name, $label, $value = "") {
        $this->message_fields[] = array('field' => $name, 'description' => $label, 'value' => $value);
    }
    
    public function addAction($key, $title, $callback) {
        $this->actions[$key] = array('title' => $title, 'action_fields' => $this->action_fields, 'message_fields' => $this->message_fields, 'callback' => $callback);
    }
    
    public function getAction($actions) {
        return array_merge($actions, $this->actions);
    }
    
    public function callback($data, $messages) {
        return $messages['failed'];
    }
    
    public function addHooks() {
        add_filter('madeit_forms_actions', array($this, 'getAction'));
    }
}