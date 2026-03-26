<?php

class WP_MADEIT_FORMS_Rest
{
    public function addHooks()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('madeit/v1', '/forms', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_forms'],
                'permission_callback' => [$this, 'can_access'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_form'],
                'permission_callback' => [$this, 'can_access'],
            ],
        ]);
    }

    public function can_access()
    {
        return current_user_can('edit_posts');
    }

    public function get_forms(WP_REST_Request $request)
    {
        $posts = get_posts([
            'post_type'      => 'ma_forms',
            'post_status'    => ['publish', 'draft', 'private', 'pending', 'future'],
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        $result = [];
        foreach ($posts as $postId) {
            $result[] = [
                'id'   => (int) $postId,
                'name' => get_the_title($postId),
            ];
        }

        return rest_ensure_response($result);
    }

    public function create_form(WP_REST_Request $request)
    {
        $title = sprintf(
            /* translators: %s: date/time */
            __('New form %s', 'forms-by-made-it'),
            wp_date('Y-m-d H:i')
        );

        $postId = wp_insert_post([
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_type'   => 'ma_forms',
        ], true);

        if (is_wp_error($postId)) {
            return $postId;
        }

        update_post_meta($postId, 'form_type', 'html');
        update_post_meta($postId, 'save_inputs', 1);
        update_post_meta($postId, 'form', '');
        update_post_meta($postId, 'messages', wp_json_encode([]));
        update_post_meta($postId, 'actions', wp_json_encode([]));

        return rest_ensure_response([
            'id' => (int) $postId,
        ]);
    }
}
