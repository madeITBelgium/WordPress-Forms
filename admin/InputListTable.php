<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class InputListTable extends WP_List_Table
{
    private $db;

    public function __construct($wp_plugin_db)
    {
        parent::__construct([
            'singular' => 'wp_list_text_link',
            'plural'   => 'wp_list_test_links',
            'ajax'     => false,
        ]);
        $this->db = $wp_plugin_db;
    }

    public function extra_tablenav($which)
    {
        if ($which == 'top') {
            //The code that goes before the table is here
        }
        if ($which == 'bottom') {
            //The code that goes after the table is there
        }
    }

    public function get_columns()
    {
        $columns = [
            'cb'          => '<input type="checkbox" />',
            'id'          => __('ID', 'forms-by-made-it'),
            'form'        => __('Form', 'forms-by-made-it'),
            'ip'          => __('IP', 'forms-by-made-it'),
            'read'        => __('Read', 'forms-by-made-it'),
            'create_time' => __('Create date', 'forms-by-made-it'),
        ];

        return $columns;
    }

    public function prepare_items()
    {
        global $wpdb, $_wp_column_headers;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $this->example_data;

        //$screen = get_current_screen();

        $query = 'SELECT * FROM '.$wpdb->prefix.'madeit_form_inputs';

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET['orderby']) ? $this->html_escape($_GET['orderby']) : 'id';
        $order = !empty($_GET['order']) ? $this->html_escape($_GET['order']) : 'DESC';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY '.$orderby.' '.$order;
        }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 25;
        //Which page is this?
        $paged = !empty($_GET['paged']) ? $this->html_escape($_GET['paged']) : '';
        //Page Number
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems / $perpage);
        //adjust the query to take pagination into account
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT '.(int) $offset.','.(int) $perpage;
        }

        /* -- Register the pagination -- */
        $this->set_pagination_args([
            'total_items' => $totalitems,
            'total_pages' => $totalpages,
            'per_page'    => $perpage,
        ]);
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        //$columns = $this->get_columns();
        //$_wp_column_headers[$screen->id]=$columns;

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
                return $item->id;
            case 'form':
                $form = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $item->form_id);
                if (is_array($form)) {
                    $form = json_decode(json_encode($form));
                }

                return esc_textarea($item->title);
            case 'ip':
                return $item->ip;
            case 'read':
                return $item->read == 1 ? __('Yes', 'forms-by-made-it') : __('No', 'forms-by-made-it');
            case 'create_time':
                return $item->create_time;
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function get_sortable_columns()
    {
        $sortable_columns = [
            'id'          => ['id', false],
            'form'        => ['form_id', false],
            'ip'          => ['ip', false],
            'read'        => ['read', false],
            'create_time' => ['create_time', false],
        ];

        return $sortable_columns;
    }

    public function column_id($item)
    {
        $actions = [
            'show'   => sprintf('<a href="?page=%s&action=%s&id=%s">'.esc_html(__('Show')).'</a>', $_REQUEST['page'], 'show', $item->id),
            'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">'.esc_html(__('Delete')).'</a>', $_REQUEST['page'], 'delete', $item->id),
        ];

        return sprintf('<a href="?page=%s&action=%s&id=%s">%s</a> %s', $_REQUEST['page'], 'edit', $item->id, $item->id, $this->row_actions($actions));
    }

    public function column_form($item)
    {
        $form = $this->db->querySingleRecord('SELECT * FROM `'.$this->db->prefix().'madeit_forms` WHERE id = %s', $item->form_id);
        if (is_array($form)) {
            $form = json_decode(json_encode($form));
        }
        $actions = [
            //'show' => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', 'madeit_form', 'edit', $form->id),
        ];

        return sprintf('<a href="?page=%s&action=%s&id=%s">%s</a> %s', 'madeit_form', 'edit', esc_textarea($form->title), esc_textarea($form->title), $this->row_actions($actions));
    }

    public function get_bulk_actions()
    {
        $actions = [
            //'delete'    => 'Delete'
        ];

        return $actions;
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="book[]" value="%s" />', $item->id);
    }

    private function html_escape($html_escape)
    {
        $html_escape = htmlspecialchars($html_escape, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $html_escape;
    }
}
