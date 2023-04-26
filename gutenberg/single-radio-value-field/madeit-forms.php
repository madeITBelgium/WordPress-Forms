<?php

function create_block_madeit_forms_single_radio_value_block_init()
{
    register_block_type(__DIR__, [
        'title' => __('Forms Single Radio Value Field', 'forms-by-made-it'),
    ]);
}
add_action('init', 'create_block_madeit_forms_single_radio_value_block_init');
