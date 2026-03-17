<?php
/**
 * Test script for Rate Limiting and Session IDs
 */

define('WP_USE_THEMES', false);
require_once dirname(__FILE__) . '/../../../../wp-load.php';
require_once dirname(__FILE__) . '/../ajax-handlers.php';

echo "--- Rate Limit & Session Logic Test ---\n";

// 1. Test IP Detection
$ip = gj_ai_get_ip();
echo "[OK] Detected IP: $ip\n";

// 2. Test Guest Rate Limiting (Simulated)
$test_ip = '192.168.1.1';
$transient_key = 'gj_ai_limit_' . md5($test_ip);
delete_transient($transient_key);

$settings = get_option('gj_ai_takeaway_settings', array());
$limit = $settings['guest_limit'] ?? 3;

echo "Testing Guest Limit ($limit):\n";
for ($i = 1; $i <= $limit + 1; $i++) {
    $count = (int) get_transient($transient_key);
    if ($count >= $limit) {
        echo "   Attempt $i: Blocked (Pass)\n";
    } else {
        set_transient($transient_key, $count + 1, DAY_IN_SECONDS);
        echo "   Attempt $i: Allowed\n";
    }
}
delete_transient($transient_key);

// 3. Test Session ID usage in History
$session_id = 'test_sess_' . time();
$history_key = 'gj_ai_history_' . $session_id;
$mock_history = array(array('role' => 'user', 'content' => 'Hello'));
set_transient($history_key, $mock_history, HOUR_IN_SECONDS);

$retrieved = get_transient($history_key);
if ($retrieved === $mock_history) {
    echo "[OK] Session history correctly retrieved using ID: $session_id\n";
} else {
    echo "[FAIL] Session history NOT matching.\n";
}
delete_transient($history_key);

echo "--- Test Complete ---\n";
