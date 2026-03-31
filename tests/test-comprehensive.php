<?php
/**
 * Comprehensive test script for SFF AI Takeaway.
 * This script will:
 * 1. Setup test data (Post meta, User meta)
 * 2. Configure mappings
 * 3. Verify context gathering
 * 4. Verify tag replacement
 */

define('WP_USE_THEMES', false);
require_once dirname(__FILE__) . '/../../../../wp-load.php';

// --- SETUP TEST DATA ---
$post_id = 1; // "Hello world!"
update_post_meta($post_id, 'eternal_ground', 'This is the sacred eternal ground of information.');
update_post_meta($post_id, 'ai_takeaway', 'This article is a classic greeting to the world.');
update_post_meta($post_id, 'manuscript_file_id', 0); // Mock file id

$author_id = get_post_field('post_author', $post_id);
update_user_meta($author_id, 'university_name', 'University of Awesome');
update_user_meta($author_id, 'country_origin', 'Wakanda');

// --- CONFIGURE SETTINGS ---
$settings = array(
    'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
    'api_key'  => 'sk-test-key-123',
    'model'    => 'google/gemini-2.0-flash-001',
    'prompt'   => "You are an assistant. Eternal Ground: {{meta:Eternal Ground}}. Title: {{post_title}}. Author Country: {{user_meta:country_origin}}.",
    'article_meta_mappings' => array(
        array('key' => 'eternal_ground', 'label' => 'Eternal Ground', 'type' => 'text'),
        array('key' => 'manuscript_file_id', 'label' => 'Manuscript', 'type' => 'file_id'),
    ),
    'user_meta_mappings' => array(
        array('key' => 'university_name', 'label' => 'University'),
        array('key' => 'country_origin', 'label' => 'Country'),
    )
);
update_option('sff_ai_takeaway_settings', $settings);

// --- EXECUTE TEST ---
echo "--- COMPREHENSIVE TEST ---\n";

// 1. Gather Context
$context = sff_ai_gather_post_context($post_id);
echo "1. Context Gathering:\n";
echo "   - Title: " . $context['title'] . "\n";
echo "   - Meta 'Eternal Ground': " . $context['meta']['Eternal Ground'] . "\n";
echo "   - Author University: " . $context['author']['University'] . "\n";
echo "   - Author Country: " . $context['author']['Country'] . "\n";

if ($context['meta']['Eternal Ground'] === 'This is the sacred eternal ground of information.') {
    echo "   [OK] Meta mapping works.\n";
} else {
    echo "   [FAIL] Meta mapping failed.\n";
}

// 2. Tag Replacement
echo "\n2. Tag Replacement:\n";
$rendered = sff_ai_replace_tags($settings['prompt'], $context);
echo "   - Rendered Prompt: " . $rendered . "\n";

if (strpos($rendered, 'sacred eternal ground') !== false && strpos($rendered, 'Wakanda') !== false) {
    echo "   [OK] Tag replacement works.\n";
} else {
    echo "   [FAIL] Tag replacement failed.\n";
}

// 3. Shortcode check
echo "\n3. Shortcode Check:\n";
$shortcode_out = do_shortcode('[ai_takeaway post_id="1"]');
if (strpos($shortcode_out, 'sff-ai-takeaway-container') !== false) {
    echo "   [OK] Shortcode output found.\n";
    if (strpos($shortcode_out, 'greeting to the world') !== false) {
        echo "   [OK] Shortcode includes correct takeaway data.\n";
    } else {
        echo "   [FAIL] Shortcode missing takeaway data.\n";
    }
} else {
    echo "   [FAIL] Shortcode output not found.\n";
}

echo "\n--- TEST COMPLETE ---\n";
