<?php

function create_block_madeit_forms_multi_value_block_init()
{
    register_block_type(__DIR__, [
        'title' => __('Forms Multi Value Field', 'forms-by-made-it'),
    ]);
}
add_action('init', 'create_block_madeit_forms_multi_value_block_init');
