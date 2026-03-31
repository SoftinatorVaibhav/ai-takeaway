<?php
/**
 * Plugin Name: SFF AI Takeaway
 * Description: AI-powered article takeaway and chat interface.
 * Version: 1.0.0
 * Author: SFF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'SFF_AI_TAKEAWAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'SFF_AI_TAKEAWAY_URL', plugin_dir_url( __FILE__ ) );

// Include Files
require_once SFF_AI_TAKEAWAY_DIR . 'shortcode.php';
require_once SFF_AI_TAKEAWAY_DIR . 'admin-settings.php';
require_once SFF_AI_TAKEAWAY_DIR . 'dashboard.php';
require_once SFF_AI_TAKEAWAY_DIR . 'ajax-handlers.php';

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, 'sff_ai_takeaway_activate' );
function sff_ai_takeaway_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sff_ai_logs';
    $old_table_name = $wpdb->prefix . 'gj_ai_logs';
    
    // Check if old table exists and rename it to keep data
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$old_table_name'" ) == $old_table_name ) {
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            $wpdb->query( "RENAME TABLE $old_table_name TO $table_name" );
        }
    }

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        chat_session_id varchar(100) DEFAULT NULL,
        ip_address varchar(45) NOT NULL,
        user_id bigint(20) DEFAULT NULL,
        post_id bigint(20) NOT NULL,
        query text NOT NULL,
        response text NOT NULL,
        PRIMARY KEY  (id),
        KEY chat_session_id (chat_session_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Migrate options
    $old_settings = get_option( 'gj_ai_takeaway_settings' );
    if ( $old_settings && ! get_option( 'sff_ai_takeaway_settings' ) ) {
        update_option( 'sff_ai_takeaway_settings', $old_settings );
        // Optionally delete old option? User said "nothing disrupted", maybe keep it for a while or delete it.
        // I'll keep it for safety but migrate the data.
    }

    // Initialize default options if needed
    if ( ! get_option( 'sff_ai_takeaway_settings' ) ) {
        update_option( 'sff_ai_takeaway_settings', array(
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api_key'  => '',
            'model'    => 'google/gemini-2.0-flash-001',
            'model_login' => 'google/gemini-2.0-flash-001',
            'model_guest' => 'google/gemini-2.0-flash-lite-001',
            'prompt'   => "You are a helpful assistant for the article: {{post_title}}. \n\nContext: {{post_content}}\n\nEternal Ground Info: {{eternal_ground}}\n\nHelp the user answer questions based on this information.",
            'log_retention' => 30,
            'guest_limit' => 3,
            'user_limit' => 100,
        ) );
    }

    // Schedule cleanup cron if not exists
    if ( ! wp_next_scheduled( 'sff_ai_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'sff_ai_daily_cleanup' );
    }
}

/**
 * Deactivation Hook
 */
register_deactivation_hook( __FILE__, 'sff_ai_takeaway_deactivate' );
function sff_ai_takeaway_deactivate() {
    wp_clear_scheduled_hook( 'sff_ai_daily_cleanup' );
}

/**
 * Daily Cleanup Task
 */
add_action( 'sff_ai_daily_cleanup', 'sff_ai_perform_log_cleanup' );
function sff_ai_perform_log_cleanup() {
    $settings = get_option( 'sff_ai_takeaway_settings', array() );
    $retention_days = isset( $settings['log_retention'] ) ? intval( $settings['log_retention'] ) : 30;

    if ( $retention_days > 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sff_ai_logs';
        $wpdb->query( $wpdb->prepare( 
            "DELETE FROM $table_name WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)", 
            $retention_days 
        ) );
    }
}


