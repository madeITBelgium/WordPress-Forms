<?php
class DatabaseInit {
    public function __construct() {
        
    }
    
    public function dbv1() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $table_name = $wpdb->prefix . 'madeit_forms';
	    $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                form TEXT DEFAULT NULL,
                actions TEXT DEFAULT NULL,
                messages TEXT DEFAULT NULL,
                create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                primary KEY (id)
            ) $charset_collate;";
        dbDelta($sql);
        
        add_option('madeit_form_db_v', 1);
    }
    
    public function dbv2() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $table_name = $wpdb->prefix . 'madeit_form_inputs';
	    $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                form_id int(10) NOT NULL,
                data TEXT DEFAULT NULL,
                ip VARCHAR(255) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                spam int(2) DEFAULT 0,
                read INT(2) DEFAULT 0,
                result VARCHAR(255) DEFAULT NULL,
                create_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                primary KEY (id)
            ) $charset_collate;";
        dbDelta($sql);
        
        update_option('madeit_form_db_v', 2);
    }
    
    public function addHooks() {
        if(get_option('madeit_form_db_v') === null || get_option('madeit_form_db_v') < 1) {
            $this->dbv1();
        }
        if(get_option('madeit_form_db_v') === null || get_option('madeit_form_db_v') < 2) {
            $this->dbv2();
        }
    }
}