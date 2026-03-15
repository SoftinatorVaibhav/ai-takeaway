<?php
require_once dirname(__FILE__) . '/../../../../wp-load.php';
$post_id = 1;
update_post_meta($post_id, 'ai_takeaway', 'This article is a classic greeting to the world.');
$out = do_shortcode('[ai_takeaway post_id="1"]');
if (strpos($out, 'greeting to the world') !== false) {
    echo "FOUND";
} else {
    echo "NOT FOUND\n";
    echo "DATA: " . get_post_meta($post_id, 'ai_takeaway', true) . "\n";
    echo "OUTPUT CLUE: " . substr(strip_tags($out), 0, 100) . "\n";
}
