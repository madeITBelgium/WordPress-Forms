<?php

function create_block_madeit_forms_submit_block_init()
{
    register_block_type(__DIR__, [
        'title' => __('Forms Submit Field', 'forms-by-made-it'),
    ]);
}
add_action('init', 'create_block_madeit_forms_submit_block_init');
