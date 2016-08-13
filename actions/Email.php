<?php
if(!class_exists("WP_MADEIT_FORM_Action")) {
    require_once(MADEIT_FORM_DIR . '/actions/WP_MADEIT_FORM_Action.php');
}
class WP_MADEIT_FORM_Email extends WP_MADEIT_FORM_Action {
    public function __construct() {
        $this->addActionField('to', __('To', 'madeit_forms'), 'text', get_bloginfo('admin_email'));
        $this->addActionField('from', __('From', 'madeit_forms'), 'text', "[your-name] <" . get_bloginfo('admin_email') . ">");
        $this->addActionField('subject', __('Subject', 'madeit_forms'), 'text', "[your-subject]");
        $this->addActionField('header', __('Header', 'madeit_forms'), 'textarea', "Reply-to: [your-email]");
        $this->addActionField('message', __('Message', 'madeit_forms'), 'textarea', "From: [your-name] <[your-email]>\nSubject: [your-subject]\n\nMessage:[your-message]", ['min-height' => "250px"]);
        
        
        $this->addMessageField('action_email_email_error', __('The email can\'t be send.', 'madeit_forms'), __("Sorry, there was an error while processing your data. The admin is contacted.", "madeit_forms"));
        
        $this->addAction('EMAIL', __('E-mail', 'madeit_forms'), array($this, 'callback'));
        
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