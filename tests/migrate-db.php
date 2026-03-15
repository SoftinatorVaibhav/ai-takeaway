<?php
require_once dirname(__FILE__) . '/../../../../wp-load.php';
global $wpdb;
$table_name = $wpdb->prefix . 'gj_ai_logs';
$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table_name' AND COLUMN_NAME = 'chat_session_id'");
if (empty($row)) {
    $wpdb->query("ALTER TABLE $table_name ADD chat_session_id varchar(100) DEFAULT NULL AFTER time");
    $wpdb->query("ALTER TABLE $table_name ADD INDEX (chat_session_id)");
    echo "COLUMN ADDED";
} else {
    echo "ALREADY EXISTS";
}
