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
            $ar[$key]['options'] = $this->tag_generator_options();
        }
        return array_merge($actions, $ar);
    }
    
    public function tag_generator_options() {
        return [
            'label' => [
                'text' => __('Label', 'forms-by-made-it'),
                'type' => 'text',
            ],
            'id' => [
                'text' => __('Id attribute', 'forms-by-made-it'),
                'type' => 'text',
            ],
            'name' => [
                'text' => __('Name', 'forms-by-made-it'),
                'type' => 'text',
            ],
            'required' => [
                'text' => __('Required field', 'forms-by-made-it'),
                'type' => 'checkbox',
            ],
            'value' => [
                'text' => __('Default value', 'forms-by-made-it'),
                'type'=> 'text',
            ],
            'placeholder' => [
                'text' => __('Placeholder', 'forms-by-made-it'),
                'type' => 'text',
            ],
            'class' => [
                'text' => __('Class attribute', 'forms-by-made-it'),
                'type' => 'text',
            ],
        ];
    }
    
    public function addHooks() {
        add_filter('madeit_forms_modules', array($this, 'getAction'));
    }
}