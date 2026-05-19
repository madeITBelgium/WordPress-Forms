<?php

function madeit_forms_register_main_block()
{
    register_block_type(
        MADEIT_FORM_DIR.'/gutenberg/madeit-forms',
        [
            'render_callback' => 'madeit_forms_render_main_block',
        ]
    );
}
add_action('init', 'madeit_forms_register_main_block');

function madeit_forms_render_main_block($attributes)
{
    if (empty($attributes['formId'])) {
        return '<p>Selecteer een formulier.</p>';
    }

    $form_id = intval($attributes['formId']);

    return do_shortcode('[form id="'.$form_id.'"]');
}
