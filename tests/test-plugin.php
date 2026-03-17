<?php
/**
 * Test script for GJ AI Takeaway plugin functionality.
 * Run via CLI: php test-plugin.php
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

echo "--- GJ AI Takeaway Plugin Test ---\n";

// 1. Verify Database Table for Logs
global $wpdb;
$table_name = $wpdb->prefix . 'gj_ai_logs';
if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    echo "[FAIL] Table $table_name does not exist.\n";
} else {
    echo "[OK] Table $table_name exists.\n";
}

// 2. Test Context Gathering
$post = get_posts(array('posts_per_page' => 1));
if(empty($post)) {
    echo "[SKIP] No posts found to test context gathering.\n";
} else {
    $post_id = $post[0]->ID;
    echo "Testing context for Post ID: $post_id\n";
    $context = gj_ai_gather_post_context($post_id);
    if(!empty($context['title'])) {
        echo "[OK] Context gathered successfully (Title: " . $context['title'] . ")\n";
    } else {
        echo "[FAIL] Failed to gather context for Post ID: $post_id\n";
    }
}

// 3. Test Rate Limiting (Simulated)
echo "Testing Rate Limiting...\n";
$ip = '127.0.0.1';
$transient_key = 'gj_ai_limit_' . md5($ip);
delete_transient($transient_key); // Reset limit

$settings = get_option('gj_ai_takeaway_settings', array());
$guest_limit = $settings['guest_limit'] ?? 3;
echo "Current Guest Limit: $guest_limit\n";

for($i = 1; $i <= $guest_limit + 1; $i++) {
    $count = (int) get_transient($transient_key);
    if($count >= $guest_limit) {
        echo "Attempt $i: [OK] Rate limit reached correctly.\n";
        break;
    } else {
        set_transient($transient_key, $count + 1, DAY_IN_SECONDS);
        echo "Attempt $i: Request allowed.\n";
    }
    if($i > $guest_limit) {
        echo "Attempt $i: [FAIL] Rate limit NOT reached as expected.\n";
    }
}
delete_transient($transient_key); // Cleanup

// 4. Test Stateful Chat (Simulated)
echo "Testing Stateful Chat...\n";
$session_id = 'test_session_' . time();
$history_key = 'gj_ai_history_' . $session_id;

// Simulate first message
$dummy_messages = array(
    array('role' => 'system', 'content' => 'Test system'),
    array('role' => 'user', 'content' => 'First message')
);
set_transient($history_key, $dummy_messages, HOUR_IN_SECONDS);
$stored = get_transient($history_key);
if($stored === $dummy_messages) {
    echo "[OK] Stateful session stored and retrieved successfully.\n";
} else {
    echo "[FAIL] Failed to store/retrieve stateful session.\n";
}
delete_transient($history_key); // Cleanup

echo "--- Test Complete ---\n";
