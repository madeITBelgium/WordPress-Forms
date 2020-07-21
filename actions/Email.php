<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_Email extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('to', __('To', 'forms-by-made-it'), 'text', get_bloginfo('admin_email'));
        $this->addActionField('from', __('From', 'forms-by-made-it'), 'text', '[your-name] <'.get_bloginfo('admin_email').'>');
        $this->addActionField('subject', __('Subject', 'forms-by-made-it'), 'text', '[your-subject]');
        $this->addActionField('header', __('Header', 'forms-by-made-it'), 'textarea', 'Reply-to: [your-email]');
        $this->addActionField('message', __('Message', 'forms-by-made-it'), 'textarea', "From: [your-name] <[your-email]>\nSubject: [your-subject]\n\nMessage:[your-message]", ['min-height' => '250px']);
        $this->addActionField('html', __('HTML', 'forms-by-made-it'), 'checkbox');

        $this->addMessageField('action_email_email_error', __('The email can\'t be send.', 'forms-by-made-it'), __('Sorry, there was an error while processing your data. The admin is contacted.', 'forms-by-made-it'));

        $this->addAction('EMAIL', __('E-mail', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages, $actionInfo)
    {
        if (isset($data['html']) && $data['html'] == 'checked') {
            $email = stripcslashes($data['message']);
        } else {
            $email = nl2br($data['message']);
        }

        add_filter('wp_mail_from', [$this, 'my_mail_from']);
        add_filter('wp_mail_from_name', [$this, 'my_mail_from_name']);
        add_filter('wp_mail_content_type', [$this, 'set_html_mail_content_type']);

        $result = wp_mail($data['to'], $data['subject'], $email, $data['header']);
        if ($result !== true) {
            return isset($messages['action_email_email_error']) ? $messages['action_email_email_error'] : $messages['failed'];
        }
        remove_filter('wp_mail_content_type', [$this, 'set_html_mail_content_type']);

        return true;
    }

    public function my_mail_from($email)
    {
        return empty($data['from']) ? get_bloginfo('admin_email') : $data['from'];
    }

    public function my_mail_from_name($name)
    {
        return  get_bloginfo('name');
    }

    public function set_html_mail_content_type()
    {
        return 'text/html';
    }
}
