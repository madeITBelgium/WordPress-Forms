<?php

if (!class_exists('WP_MADEIT_FORM_Action')) {
    require_once MADEIT_FORM_DIR.'/actions/WP_MADEIT_FORM_Action.php';
}
class WP_MADEIT_FORM_Download extends WP_MADEIT_FORM_Action
{
    public function __construct()
    {
        $this->addActionField('download_url', __('Download URL', 'forms-by-made-it'), 'text', '');

        $this->addAction('DOWNLOAD', __('File download', 'forms-by-made-it'), [$this, 'callback']);

        $this->addHooks();
    }

    public function callback($data, $messages)
    {
        return ['type' => 'HTML', 'code' => "<script>window.open('".$data['download_url']."', '_blank');</script>"];
    }
}
