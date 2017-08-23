<?php
class WP_MADEIT_FORM_Module {
    private $tags = array();
    private $message_fields = array();
    
    public function __construct() {
        $this->addHooks();
    }
    
    public function addTag($name, $title, $content, $form, $validation) {
        $this->tags[$name] = array('title' => $title, 'content' => $content, 'form' => $form, 'validation' => $validation);
    }
        
    public function addMessageField($tag, $name, $label, $value = "") {
        $this->message_fields[$tag][] = array('field' => $name, 'description' => $label, 'value' => $value);
    }
    
    public function getAction($actions) {
        $ar = array();
        foreach($this->tags as $key => $tag) {
            $ar[$key] = $tag;
            $ar[$key]['message_fields'] = isset($this->message_fields[$key]) && is_array($this->message_fields[$key]) ? $this->message_fields[$key] : array();
        }
        return array_merge($actions, $ar);
    }
    
    public function addHooks() {
        add_filter('madeit_forms_modules', array($this, 'getAction'));
    }
}