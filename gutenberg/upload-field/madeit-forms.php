<?php

function create_block_madeit_forms_upload_block_init()
{
    register_block_type(__DIR__, [
        'title' => __('Forms Upload Field', 'forms-by-made-it'),
    ]);
}
add_action('init', 'create_block_madeit_forms_upload_block_init');
