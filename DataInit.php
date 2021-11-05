<?php

class DataInit
{
    public function __construct()
    {
    }

    public function dbMigrate()
    {
        global $wpdb;
        $forms = $wpdb->get_results('SELECT * FROM `'.$wpdb->base_prefix.'madeit_forms`', ARRAY_A);
        foreach ($forms as $form) {
            $existingFroms = get_posts([
                'post_type'  => 'ma_forms',
                'meta_query' => [
                    [
                        'key'   => 'form_id',
                        'value' => $form['id'],
                    ],
                ],
            ]);

            if (count($existingFroms) === 0) {
                $formId = wp_insert_post([
                    'post_title'  => wp_strip_all_tags($form['title']),
                    'post_status' => 'publish',
                    'post_type'   => 'ma_forms',
                    'post_date'   => $form['create_time'],
                ]);

                update_post_meta($formId, 'form_id', $form['id']);
                update_post_meta($formId, 'form', $form['form']);
                update_post_meta($formId, 'messages', $this->enterToDB($form['messages']));
                update_post_meta($formId, 'actions', $this->enterToDB($form['actions']));
                update_post_meta($formId, 'form_type', 'html');
                update_post_meta($formId, 'save_inputs', 1);
            }
        }

        usleep(rand(100000, 1500000));

        $inputs = $wpdb->get_results('SELECT * FROM `'.$wpdb->base_prefix.'madeit_form_inputs`', ARRAY_A);
        foreach ($inputs as $input) {
            $existingInputs = get_posts([
                'post_type'  => 'ma_form_inputs',
                'meta_query' => [
                    [
                        'key'   => 'old_input_id',
                        'value' => $input['id'],
                    ],
                ],
            ]);

            if (count($existingInputs) === 0) {
                $existingForms = get_posts([
                    'post_type'  => 'ma_forms',
                    'meta_query' => [
                        [
                            'key'   => 'form_id',
                            'value' => $input['form_id'],
                        ],
                    ],
                ]);

                $inputId = wp_insert_post([
                    'post_title'  => 'Form submit '.($existingForms[0]->post_title ?? '').' - '.$input['ip'],
                    'post_status' => 'publish',
                    'post_type'   => 'ma_form_inputs',
                    'post_date'   => $input['create_time'],
                ]);

                update_post_meta($inputId, 'form_id', $existingForms[0]->ID ?? $input['form_id']);
                update_post_meta($inputId, 'data', $this->enterToDB($input['data']));
                update_post_meta($inputId, 'ip', $input['ip']);
                update_post_meta($inputId, 'user_agent', $input['user_agent']);
                update_post_meta($inputId, 'spam', $input['spam']);
                update_post_meta($inputId, 'read', $input['read'] == 1 ? 1 : 0);
                update_post_meta($inputId, 'result', $input['result']);
                update_post_meta($inputId, 'old_input_id', $input['id']);
            }
        }

        update_option('madeit_form_db_v', 3);
    }

    public function add_role_caps()
    {
        // Add the roles you'd like to administer the custom post types
        $roles = ['ma_form_role', 'editor', 'administrator', 'author'];

        // Loop through each role and assign capabilities
        foreach ($roles as $the_role) {
            $role = get_role($the_role);

            $role->add_cap('read');
            $role->add_cap('read_form_role');
            $role->add_cap('read_private_form_roles');
            $role->add_cap('edit_form_role');
            $role->add_cap('edit_form_roles');
            $role->add_cap('edit_others_form_roles');
            $role->add_cap('edit_published_form_roles');
            $role->add_cap('publish_form_roles');
            $role->add_cap('delete_others_form_roles');
            $role->add_cap('delete_private_form_roles');
            $role->add_cap('delete_published_form_roles');
        }
    }

    /* Create post types */
    public function create_post_type()
    {
        add_role('ma_form_role', 'Forms management', ['read' => true, 'edit_posts' => false, 'delete_posts' => false, 'publish_posts' => false, 'upload_files' => true]);

        $capabilities = [
            'edit_post'             => 'edit_form',
            'read_post'             => 'read_forms',
            'delete_post'           => 'delete_form',
            'edit_posts'            => 'edit_forms',
            'edit_others_posts'     => 'edit_others_forms',
            'publish_posts'         => 'publish_forms',
            'read_private_posts'    => 'read_private_forms',
        ];

        $labels = [
            'name'                  => _x('Forms', 'Post Type General Name', 'forms-by-made-it'),
            'singular_name'         => _x('Form', 'Post Type Singular Name', 'forms-by-made-it'),
            'menu_name'             => __('Forms', 'forms-by-made-it'),
            'name_admin_bar'        => __('Forms', 'forms-by-made-it'),
            'archives'              => __('Item Archives', 'forms-by-made-it'),
            'attributes'            => __('Item Attributes', 'forms-by-made-it'),
            'parent_item_colon'     => __('Parent Item:', 'forms-by-made-it'),
            'all_items'             => __('Forms', 'forms-by-made-it'),
            'add_new_item'          => __('Add New Item', 'forms-by-made-it'),
            'add_new'               => __('Add New', 'forms-by-made-it'),
            'new_item'              => __('New Item', 'forms-by-made-it'),
            'edit_item'             => __('Edit Item', 'forms-by-made-it'),
            'update_item'           => __('Update Item', 'forms-by-made-it'),
            'view_item'             => __('View Item', 'forms-by-made-it'),
            'view_items'            => __('View Items', 'forms-by-made-it'),
            'search_items'          => __('Search Item', 'forms-by-made-it'),
            'not_found'             => __('Not found', 'forms-by-made-it'),
            'not_found_in_trash'    => __('Not found in Trash', 'forms-by-made-it'),
            'featured_image'        => __('Featured Image', 'forms-by-made-it'),
            'set_featured_image'    => __('Set featured image', 'forms-by-made-it'),
            'remove_featured_image' => __('Remove featured image', 'forms-by-made-it'),
            'use_featured_image'    => __('Use as featured image', 'forms-by-made-it'),
            'insert_into_item'      => __('Insert into item', 'forms-by-made-it'),
            'uploaded_to_this_item' => __('Uploaded to this item', 'forms-by-made-it'),
            'items_list'            => __('Items list', 'forms-by-made-it'),
            'items_list_navigation' => __('Items list navigation', 'forms-by-made-it'),
            'filter_items_list'     => __('Filter items list', 'forms-by-made-it'),
        ];
        $args = [
            'label'                 => __('Form', 'forms-by-made-it'),
            'description'           => __('Made I.T. Forms data', 'forms-by-made-it'),
            'labels'                => $labels,
            'supports'              => ['title', 'custom-fields'],
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-email',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'show_in_rest'          => true,
            'rest_base'             => 'forms',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
        ];
        register_post_type('ma_forms', $args);

        $labels = [
            'name'                  => _x('Submitted forms', 'Post Type General Name', 'forms-by-made-it'),
            'singular_name'         => _x('Submitted form', 'Post Type Singular Name', 'forms-by-made-it'),
            'menu_name'             => __('Submitted forms', 'forms-by-made-it'),
            'name_admin_bar'        => __('Submitted forms', 'forms-by-made-it'),
            'archives'              => __('Item Archives', 'forms-by-made-it'),
            'attributes'            => __('Item Attributes', 'forms-by-made-it'),
            'parent_item_colon'     => __('Parent Item:', 'forms-by-made-it'),
            'all_items'             => __('Submitted forms', 'forms-by-made-it'),
            'add_new_item'          => __('Add New Item', 'forms-by-made-it'),
            'add_new'               => __('Add New', 'forms-by-made-it'),
            'new_item'              => __('New Item', 'forms-by-made-it'),
            'edit_item'             => __('Edit Item', 'forms-by-made-it'),
            'update_item'           => __('Update Item', 'forms-by-made-it'),
            'view_item'             => __('View Item', 'forms-by-made-it'),
            'view_items'            => __('View Items', 'forms-by-made-it'),
            'search_items'          => __('Search Item', 'forms-by-made-it'),
            'not_found'             => __('Not found', 'forms-by-made-it'),
            'not_found_in_trash'    => __('Not found in Trash', 'forms-by-made-it'),
            'featured_image'        => __('Featured Image', 'forms-by-made-it'),
            'set_featured_image'    => __('Set featured image', 'forms-by-made-it'),
            'remove_featured_image' => __('Remove featured image', 'forms-by-made-it'),
            'use_featured_image'    => __('Use as featured image', 'forms-by-made-it'),
            'insert_into_item'      => __('Insert into item', 'forms-by-made-it'),
            'uploaded_to_this_item' => __('Uploaded to this item', 'forms-by-made-it'),
            'items_list'            => __('Items list', 'forms-by-made-it'),
            'items_list_navigation' => __('Items list navigation', 'forms-by-made-it'),
            'filter_items_list'     => __('Filter items list', 'forms-by-made-it'),
        ];
        $args = [
            'label'                 => __('Submitted form', 'forms-by-made-it'),
            'description'           => __('Made I.T. Forms submitted data', 'forms-by-made-it'),
            'labels'                => $labels,
            'supports'              => [/*'title',*/ 'custom-fields'],
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => current_user_can('edit_posts') ? 'edit.php?post_type=ma_forms' : false,
            'menu_position'         => 5,
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'show_in_rest'          => true,
            'rest_base'             => 'form_inputs',
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
        ];
        register_post_type('ma_form_inputs', $args);
    }

    public function remove_wp_seo_meta_box()
    {
        remove_meta_box('wpseo_meta', 'ma_forms', 'normal');
        remove_meta_box('wpseo_meta', 'ma_form_inputs', 'normal');
    }

    public function yoast_seo_admin_remove_columns($columns)
    {
        unset($columns['wpseo-score']);
        unset($columns['wpseo-score-readability']);
        unset($columns['wpseo-title']);
        unset($columns['wpseo-metadesc']);
        unset($columns['wpseo-focuskw']);
        unset($columns['wpseo-links']);
        unset($columns['wpseo-linked']);

        return $columns;
    }

    public function addHooks()
    {
        if (get_option('madeit_form_db_v') !== null && get_option('madeit_form_db_v') < 3) {
            //add_action('init', [$this, 'dbMigrate']);
        }

        //add_action('admin_init', [$this, 'add_role_caps'], 999);
        add_action('init', [$this, 'create_post_type']);
        add_action('add_meta_boxes', [$this, 'remove_wp_seo_meta_box'], 100);

        add_filter('manage_edit-ma_forms_columns', [$this, 'yoast_seo_admin_remove_columns'], 10, 1);
        add_filter('manage_edit-ma_form_inputs_columns', [$this, 'yoast_seo_admin_remove_columns'], 10, 1);
    }

    public function enterToDB($data)
    {
        $data = str_replace('\r\n', '|--MAFORM-RN--|', $data);
        $data = str_replace('\r', '|--MAFORM-R--|', $data);
        $data = str_replace('\n', '|--MAFORM-N--|', $data);

        return $data;
    }

    public function dbToEnter($data)
    {
        $data = str_replace('|--MAFORM-RN--|', '\r\n', $data);
        $data = str_replace('|--MAFORM-R--|', '\r', $data);
        $data = str_replace('|--MAFORM-N--|', '\n', $data);

        return $data;
    }
}
