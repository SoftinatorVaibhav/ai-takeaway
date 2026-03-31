<?php
/**
 * AJAX Handlers for GJ AI Takeaway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GJ_AI_TAKEAWAY_DIR . 'ai-client.php';

/**
 * Gather Context for a Post
 */
function gj_ai_gather_post_context( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) return array();

    $settings = get_option( 'gj_ai_takeaway_settings', array() );
    
    // Author Data (Dynamic)
    $author_id = $post->post_author;
    $author_data = array();
    $user_meta_mappings = $settings['user_meta_mappings'] ?? array();
    
    foreach ( $user_meta_mappings as $um ) {
        $key = $um['key'] ?? '';
        $label = $um['label'] ?? $key;
        if ( !$key ) continue;

        if ( in_array($key, array('display_name', 'user_email', 'first_name', 'last_name', 'nickname', 'description')) ) {
            $author_data[$label] = get_the_author_meta( $key, $author_id );
        } else {
            $author_data[$label] = get_user_meta( $author_id, $key, true );
        }
    }

    // Ensure email exists if not mapped
    if ( empty($author_data['Email Address']) && empty($author_data['email']) ) {
        $author_data['email'] = get_the_author_meta( 'user_email', $author_id );
    }
    // Ensure name exists
    if ( empty($author_data['Full Name']) && empty($author_data['name']) ) {
        $author_data['name'] = get_the_author_meta( 'display_name', $author_id );
    }



    // Meta Fields & Dynamic Mapping
    $meta_fields = array();
    $attachments_binary = array();
    $meta_mappings = $settings['article_meta_mappings'] ?? array();
    
    foreach ( $meta_mappings as $m ) {
        $key = $m['key'] ?? '';
        $label = $m['label'] ?? $key;
        $type = $m['type'] ?? 'text';
        
        if ( !$key ) continue;
        
        $value = get_post_meta( $post_id, $key, true );
        
        if ( $type === 'file_id' && is_numeric($value) ) {
            $meta_fields[$label] = array(
                'name' => get_the_title($value),
                'url'  => wp_get_attachment_url($value),
            );
        } elseif ( $type === 'file_url' ) {
            $meta_fields[$label] = array(
                'url' => $value
            );
        } elseif ( $type === 'file_binary' ) {
            // New: Handle Binary Attachment
            $file_url = '';
            if ( is_numeric($value) ) {
                $file_url = wp_get_attachment_url($value);
            } else {
                $file_url = $value; // Assume URL
            }

            if ( $file_url ) {
                $binary = gj_ai_get_file_binary($file_url);
                if ( $binary ) {
                    $attachments_binary[] = array(
                        'label' => $label,
                        'mime'  => wp_check_filetype($file_url)['type'] ?: 'application/octet-stream',
                        'data'  => $binary
                    );
                }
            }
        } else {
            $meta_fields[$label] = $value;
        }
    }

    // Related Files (General attachments)
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => 10,
        'post_parent' => $post_id
    ));
    $files = array();
    foreach($attachments as $att) {
        $files[] = array(
            'name' => $att->post_title,
            'url' => wp_get_attachment_url($att->ID)
        );
    }

    // 4. Fetch Wasabi Manuscript (OCR) content if available
    $ocr_content = '';
    $file_id = get_post_meta( $post_id, 'file_id', true );
    if ( $file_id && class_exists( 'WMA_S3_Helper' ) ) {
        $md_content = WMA_S3_Helper::fetch_markdown_from_s3( $file_id, $post_id );
        if ( ! is_wp_error( $md_content ) ) {
            $ocr_content = $md_content;
            $attachments_binary[] = array(
                'label' => 'Manuscript (OCR)',
                'mime'  => 'text/markdown',
                'data'  => $md_content
            );
        }
    }

    // Article Content
    $content = strip_tags( $post->post_content );

    return array(
        'post_id'     => $post_id,
        'title'       => $post->post_title,
        'author'      => $author_data,
        'meta'        => $meta_fields,
        'files'       => $files,
        'content'     => $content,
        'ocr_content' => $ocr_content, // Attached the OCR markdown version
        'attachments_binary' => $attachments_binary, // New: Raw binary attachments
        '_debug'      => array(
            'raw_meta' => get_post_custom( $post_id ),
            'raw_author_meta' => get_user_meta( $author_id ),
        )
    );
}

/**
 * Replace Dynamic Tags in a String
 */
function gj_ai_replace_tags( $text, $context ) {
    $post_id = $context['post_id'] ?? 0;

    $post = get_post($post_id);
    $author_id = $post ? $post->post_author : 0;

    $replacements = array(
        '{{post_id}}'      => $post_id,
        '{{post_title}}'   => $context['title'] ?? '',
        '{{post_content}}' => $context['content'] ?? '',
        '{{author_name}}'  => $context['author']['name'] ?? '',
        '{{author_university}}' => $context['author']['university'] ?? '',
        '{{author_country}}'    => $context['author']['country'] ?? '',
        '{{author_email}}'      => $context['author']['email'] ?? '',
        '{{site_url}}'          => get_site_url(),
        '{{eternal_ground}}'    => get_post_meta($post_id, 'eternal_ground', true),
        '{{ocr_content}}'       => $context['ocr_content'] ?? '',
    );

    // Replace basic tags
    foreach ( $replacements as $tag => $val ) {
        $text = str_replace( $tag, $val, $text );
    }

    // Replace meta tags: {{meta:key}}
    if ( preg_match_all( '/{{meta:(.*?)}}/', $text, $matches ) ) {
        foreach ( $matches[1] as $idx => $key ) {
            $val = get_post_meta( $post_id, $key, true );
            
            // If it's empty, check if we have a mapped label for it
            if ( empty($val) && isset($context['meta'][$key]) ) {
                $val = $context['meta'][$key];
            }

            if ( is_array($val) ) $val = wp_json_encode($val);
            $text = str_replace( $matches[0][$idx], $val, $text );
        }
    }

    // Replace user meta tags: {{user_meta:key}}
    if ( preg_match_all( '/{{user_meta:(.*?)}}/', $text, $matches ) ) {
        foreach ( $matches[1] as $idx => $key ) {
            $val = get_user_meta( $author_id, $key, true );
            if ( is_array($val) ) $val = wp_json_encode($val);
            $text = str_replace( $matches[0][$idx], $val, $text );
        }
    }

    return $text;
}



/**
 * Get Client IP
 */
function gj_ai_get_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Get File Binary Content from various sources
 */
function gj_ai_get_file_binary( $source ) {
    if ( ! $source ) return null;

    // 1. If it's a local file path
    if ( file_exists( $source ) ) {
        return file_get_contents( $source );
    }

    // 2. If it's a URL (remote or S3)
    $response = wp_remote_get( $source, array( 'timeout' => 30 ) );
    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
        return wp_remote_retrieve_body( $response );
    }

    return null;
}

/**
 * Handle Frontend Chat AJAX
 */
add_action( 'wp_ajax_gj_ai_chat', 'gj_ai_chat_handler' );
add_action( 'wp_ajax_nopriv_gj_ai_chat', 'gj_ai_chat_handler' );
function gj_ai_chat_handler() {
    $post_id = intval( $_POST['post_id'] );
    $user_msg = sanitize_text_field( $_POST['message'] );
    $chat_session_id = sanitize_text_field( $_POST['chat_session_id'] ?? '' );
    
    if ( ! $post_id || ! $user_msg ) wp_send_json_error( 'Invalid request' );

    $settings = get_option( 'gj_ai_takeaway_settings', array() );
    $is_logged_in = is_user_logged_in();
    $ip = gj_ai_get_ip();
    $user_id = get_current_user_id();

    // --- Rate Limiting ---
    $guest_limit = isset($settings['guest_limit']) ? intval($settings['guest_limit']) : 3;
    $user_limit  = isset($settings['user_limit']) ? intval($settings['user_limit']) : 100;

    if ( !$is_logged_in ) {
        $transient_key = 'gj_ai_limit_' . md5($ip);
        $count = (int) get_transient($transient_key);
        if ( $count >= $guest_limit ) {
            wp_send_json_error( array('limit_reached' => true, 'message' => 'Daily limit reached. Please login for unlimited chats.') );
        }
        set_transient($transient_key, $count + 1, DAY_IN_SECONDS);
    } else {
        $transient_key = 'gj_ai_limit_user_' . $user_id;
        $count = (int) get_transient($transient_key);
        if ( $count >= $user_limit ) {
            wp_send_json_error( array('limit_reached' => true, 'message' => 'You have reached your daily limit of chats.') );
        }
        set_transient($transient_key, $count + 1, DAY_IN_SECONDS);
    }


    // --- Stateful Chat Flow ---
    $history_transient = 'gj_ai_history_' . $chat_session_id;
    $messages = get_transient($history_transient);

    $system_prompt = $settings['prompt'] ?? '';
    
    if ( !$messages ) {
        // First message: Include Context
        $context = gj_ai_gather_post_context( $post_id );
        
        // Separate binaries for multimodal sending
        $binaries = $context['attachments_binary'] ?? array();
        unset($context['attachments_binary']);
        unset($context['_debug']); // Save tokens

        // Render Prompt
        $rendered_prompt = gj_ai_replace_tags( $system_prompt, $context );

        $user_text = "CONTEXT DATA:\n" . wp_json_encode( $context ) . "\n\nQUESTION: " . $user_msg;
        
        if ( !empty($binaries) ) {
            $content_parts = array(
                array( 'type' => 'text', 'text' => $user_text )
            );
            foreach ( $binaries as $bin ) {
                $content_parts[] = array(
                    'type' => 'image_url', // Standard multimodal format
                    'image_url' => array(
                        'url' => 'data:' . $bin['mime'] . ';base64,' . base64_encode($bin['data'])
                    )
                );
            }
            $messages = array(
                array( 'role' => 'system', 'content' => $rendered_prompt ),
                array( 'role' => 'user', 'content' => $content_parts ),
            );
        } else {
            $messages = array(
                array( 'role' => 'system', 'content' => $rendered_prompt ),
                array( 'role' => 'user', 'content' => $user_text ),
            );
        }
    } else {
        // Subsequent message: Just history + message
        $messages[] = array( 'role' => 'user', 'content' => $user_msg );
    }

    // Determine which model to use
    $default_model = $settings['model'] ?? 'google/gemini-2.0-flash-001';
    $model_to_use = $is_logged_in 
        ? ($settings['model_login'] ?? $default_model) 
        : ($settings['model_guest'] ?? $default_model);

    $client = new GJ_AI_Client( $model_to_use );
    $ai_response = $client->get_response( $messages );

    if ( strpos($ai_response, 'Error:') === 0 ) {
        wp_send_json_error( array('message' => $ai_response) );
    }

    // Save history (Limit to last 10 turns to save space/tokens)
    $messages[] = array( 'role' => 'assistant', 'content' => $ai_response );
    if(count($messages) > 20) $messages = array_merge(array($messages[0]), array_slice($messages, -19));
    set_transient($history_transient, $messages, HOUR_IN_SECONDS * 2);

    // --- Logging ---
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'gj_ai_logs', array(
        'chat_session_id' => $chat_session_id,
        'ip_address' => $ip,
        'user_id'    => $user_id ? $user_id : null,
        'post_id'    => $post_id,
        'query'      => $user_msg,
        'response'   => $ai_response
    ));

    wp_send_json_success( $ai_response );
}

/**
 * Handle Tester AJAX
 */
add_action( 'wp_ajax_gj_ai_test_context', 'gj_ai_test_context_handler' );
function gj_ai_test_context_handler() {
    check_ajax_referer( 'gj_ai_test_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

    $post_id = intval( $_POST['post_id'] );
    $context = gj_ai_gather_post_context( $post_id );
    
    if ( empty( $context ) ) wp_send_json_error( 'Post not found' );

    $settings = get_option( 'gj_ai_takeaway_settings', array() );

    // Determine which model to use
    $is_logged_in = is_user_logged_in();
    $default_model = $settings['model'] ?? 'google/gemini-2.0-flash-001';
    $model_to_use = $is_logged_in 
        ? ($settings['model_login'] ?? $default_model) 
        : ($settings['model_guest'] ?? $default_model);

    $client = new GJ_AI_Client( $model_to_use );
    $system_prompt = $settings['prompt'] ?? '';

    $rendered_prompt = gj_ai_replace_tags( $system_prompt, $context );

    $binaries = $context['attachments_binary'] ?? array();
    unset($context['attachments_binary']);
    
    $user_text = "CONTEXT DATA:\n" . wp_json_encode( $context ) . "\n\nTEST QUESTION: Hello, summarize this context and any attached files.";

    if ( !empty($binaries) ) {
        $content_parts = array( array( 'type' => 'text', 'text' => $user_text ) );
        foreach ( $binaries as $bin ) {
            $content_parts[] = array(
                'type' => 'image_url',
                'image_url' => array(
                    'url' => 'data:' . $bin['mime'] . ';base64,' . base64_encode($bin['data'])
                )
            );
        }
        $messages = array(
            array( 'role' => 'system', 'content' => $rendered_prompt ),
            array( 'role' => 'user', 'content' => $content_parts ),
        );
    } else {
        $messages = array(
            array( 'role' => 'system', 'content' => $rendered_prompt ),
            array( 'role' => 'user', 'content' => $user_text ),
        );
    }

    $ai_response = $client->get_response( $messages );

    wp_send_json_success( array(
        'context'         => $context,
        'rendered_prompt' => $rendered_prompt,
        'ai_response'     => $ai_response,
    ) );
}

/**
 * Handle Explore Meta AJAX
 */
add_action( 'wp_ajax_gj_ai_explore_meta', 'gj_ai_explore_meta_handler' );
function gj_ai_explore_meta_handler() {
    check_ajax_referer( 'gj_ai_test_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

    $post_id = intval( $_POST['post_id'] );
    if ( ! $post_id ) wp_send_json_error( 'Invalid Post ID' );

    $context = gj_ai_gather_post_context( $post_id );
    if ( empty($context) ) wp_send_json_error( 'Post not found' );

    wp_send_json_success( array(
        'post_meta' => $context['_debug']['raw_meta'],
        'user_meta' => $context['_debug']['raw_author_meta'],
        'files'     => $context['files'],
        'post_data' => array(
            'title'   => $context['title'],
            'content' => wp_trim_words($context['content'], 50),
        )
    ) );
}
/**
 * Handle Shortcode Preview AJAX
 */
add_action( 'wp_ajax_gj_ai_get_shortcode_preview', 'gj_ai_get_shortcode_preview_handler' );
function gj_ai_get_shortcode_preview_handler() {
    check_ajax_referer( 'gj_ai_test_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

    $post_id = intval( $_POST['post_id'] );
    if ( ! $post_id ) wp_send_json_error( 'Invalid Post ID' );

    header('Content-Type: text/html');
    if (function_exists('gj_ai_takeaway_shortcode')) {
        echo gj_ai_takeaway_shortcode( array( 'post_id' => $post_id ) );
    } else {
        echo "Error: Shortcode function not found.";
    }
    wp_die();
}

