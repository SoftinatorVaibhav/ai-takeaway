<?php
/**
 * README & Integration Validation Script
 */

define('WP_USE_THEMES', false);
require_once dirname(__FILE__) . '/../../../../wp-load.php';

echo "--- GJ AI Takeaway validation ---\n";

// 1. Check for README
if (file_exists(dirname(__FILE__) . '/../README.md')) {
    echo "[OK] README.md found.\n";
} else {
    echo "[FAIL] README.md missing.\n";
}

// 2. Validate Shortcode Registration
if (shortcode_exists('ai_takeaway')) {
    echo "[OK] Shortcode [ai_takeaway] is registered.\n";
} else {
    echo "[FAIL] Shortcode [ai_takeaway] is NOT registered.\n";
}

// 3. Validate AJAX Actions
$ajax_actions = array('wp_ajax_gj_ai_chat', 'wp_ajax_nopriv_gj_ai_chat', 'wp_ajax_gj_ai_test_context');
foreach ($ajax_actions as $action) {
    if (has_action($action)) {
        echo "[OK] AJAX Action $action is hooked.\n";
    } else {
        echo "[FAIL] AJAX Action $action is NOT hooked.\n";
    }
}

// 4. Validate Table Structure
global $wpdb;
$table_name = $wpdb->prefix . 'gj_ai_logs';
$cols = $wpdb->get_results("DESCRIBE $table_name");
if ($cols) {
    echo "[OK] Table $table_name exists with " . count($cols) . " columns.\n";
} else {
    echo "[FAIL] Table $table_name structure invalid or missing.\n";
}

// 5. Test Tag Replacement for README Tags
require_once dirname(__FILE__) . '/../ajax-handlers.php';
$dummy_context = array(
    'title' => 'Test Article',
    'content' => 'Test Content',
    '_debug' => []
);
$test_string = "Title: {{post_title}}, URL: {{site_url}}";
$replaced = gj_ai_replace_tags($test_string, $dummy_context);

if (strpos($replaced, 'Test Article') !== false && strpos($replaced, get_site_url()) !== false) {
    echo "[OK] Dynamic Tag replacement (README documented tags) works.\n";
} else {
    echo "[FAIL] Dynamic Tag replacement failed.\n";
}

echo "--- Validation Complete ---\n";
