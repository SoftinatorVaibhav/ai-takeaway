<?php
/**
 * Refined test script for GJ AI Takeaway plugin.
 * Run via CLI: php test-plugin-v2.php
 */

define('WP_USE_THEMES', false);
// Adjust path to wp-load.php based on location. 
// If this file is in plugins/gj-ai-takeaway/
require_once dirname(__FILE__) . '/../../../../wp-load.php';

// Force Activation logic for testing
gj_ai_takeaway_activate();

$output = "";
$output .= "--- GJ AI Takeaway Plugin Diagnostic ---\n";

// 1. Database Check
global $wpdb;
$table_name = $wpdb->prefix . 'gj_ai_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
$output .= "Database Table ($table_name): " . ($table_exists ? "[OK]" : "[FAIL]") . "\n";

// 2. Settings Check
$settings = get_option('gj_ai_takeaway_settings', array());
$output .= "Settings Found: " . (!empty($settings) ? "[OK]" : "[WARNING: Empty]") . "\n";
$output .= "- Guest Limit: " . ($settings['guest_limit'] ?? 3) . "\n";
$output .= "- User Limit: " . ($settings['user_limit'] ?? 100) . "\n";
$output .= "- Article Meta Mappings: " . count($settings['article_meta_mappings'] ?? array()) . "\n";
$output .= "- User Meta Mappings: " . count($settings['user_meta_mappings'] ?? array()) . "\n";

// 3. Context Gathering Test
$posts = get_posts(array('posts_per_page' => 1));
if(!empty($posts)) {
    $post_id = $posts[0]->ID;
    $context = gj_ai_gather_post_context($post_id);
    $output .= "Context Gathering (Post $post_id): " . (!empty($context['title']) ? "[OK]" : "[FAIL]") . "\n";
    $output .= "- Title: " . ($context['title'] ?? 'N/A') . "\n";
    $output .= "- Meta Fields Found: " . count($context['meta'] ?? array()) . "\n";
} else {
    $output .= "Context Gathering: [SKIP - No Posts]\n";
}

// 4. Rate Limiting Test (Simulated for IP 8.8.8.8)
$test_ip = '8.8.8.8';
$limit_key = 'gj_ai_limit_' . md5($test_ip);
delete_transient($limit_key);

$guest_limit = $settings['guest_limit'] ?? 3;
$limit_check_passed = true;
for($i=1; $i<=$guest_limit; $i++) {
    $count = (int) get_transient($limit_key);
    if($count >= $guest_limit) { $limit_check_passed = false; break; }
    set_transient($limit_key, $count + 1, MINUTE_IN_SECONDS);
}
$final_count = (int) get_transient($limit_key);
if($final_count == $guest_limit && $limit_check_passed) {
    $output .= "Rate Limiting Simulation: [OK]\n";
} else {
    $output .= "Rate Limiting Simulation: [FAIL] (Count: $final_count, Expected: $guest_limit)\n";
}
delete_transient($limit_key);

// 6. Tag Replacement Test
$test_prompt = "Title: {{post_title}}, Content: {{post_content}}";
$rendered = gj_ai_replace_tags($test_prompt, $context);
if(strpos($rendered, $context['title']) !== false) {
    $output .= "Tag Replacement (Basic): [OK]\n";
} else {
    $output .= "Tag Replacement (Basic): [FAIL]\n";
}

echo $output;
file_put_contents(dirname(__FILE__) . '/test-results.txt', $output);
?>

