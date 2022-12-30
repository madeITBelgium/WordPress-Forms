<?php

function create_block_madeit_forms_question_seperator_block_init()
{
    register_block_type(__DIR__, [
        'title' => __('Forms Question Seperator', 'forms-by-made-it'),
    ]);
}
add_action('init', 'create_block_madeit_forms_question_seperator_block_init');
