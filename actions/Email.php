<?php
if(!class_exists("WP_MADEIT_FORM_Action")) {
    require_once(MADEIT_FORM_DIR . '/actions/WP_MADEIT_FORM_Action.php');
}
class WP_MADEIT_FORM_Email extends WP_MADEIT_FORM_Action {
    public function __construct() {
        $this->addActionField('to', __('To', 'forms-by-made-it'), 'text', get_bloginfo('admin_email'));
        $this->addActionField('from', __('From', 'forms-by-made-it'), 'text', "[your-name] <" . get_bloginfo('admin_email') . ">");
        $this->addActionField('subject', __('Subject', 'forms-by-made-it'), 'text', "[your-subject]");
        $this->addActionField('header', __('Header', 'forms-by-made-it'), 'textarea', "Reply-to: [your-email]");
        $this->addActionField('message', __('Message', 'forms-by-made-it'), 'textarea', "From: [your-name] <[your-email]>\nSubject: [your-subject]\n\nMessage:[your-message]", ['min-height' => "250px"]);
        
        
        $this->addMessageField('action_email_email_error', __('The email can\'t be send.', 'forms-by-made-it'), __("Sorry, there was an error while processing your data. The admin is contacted.", "forms-by-made-it"));
        
        $this->addAction('EMAIL', __('E-mail', 'forms-by-made-it'), array($this, 'callback'));
        
        $this->addHooks();
    }
    
    public function callback($data, $messages) {
        $result = wp_mail($data['to'], $data['subject'], nl2br($data['message']), $data['header']);
        if($result !== true) {
            return isset($messages['action_email_email_error']) ? $messages['action_email_email_error'] : $messages['failed'];
        }
        return true;
    }
}