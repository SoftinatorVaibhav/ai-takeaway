<?php
/**
 * Admin Settings for SFF AI Takeaway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register Hooks
add_action( 'admin_menu', 'sff_ai_takeaway_menu' );
// add_action( 'add_meta_boxes', 'sff_ai_add_preview_metabox' );

function sff_ai_takeaway_menu() {
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16C6.69 16 4 13.31 4 10C4 6.69 6.69 4 10 4C13.31 4 16 6.69 16 10C16 13.31 13.31 16 10 16Z" fill="white"/><path d="M10 6C7.79 6 6 7.79 6 10C6 12.21 7.79 14 10 14C12.21 14 14 12.21 14 10C14 7.79 12.21 6 10 6ZM10 12C8.9 12 8 11.1 8 10C8 8.9 8.9 8 10 8C11.1 8 12 8.9 12 10C12 11.1 11.1 12 10 12Z" fill="white"/><path d="M10 9C9.45 9 9 9.45 9 10C9 10.55 9.45 11 10 11C10.55 11 11 10.55 11 10C11 9.45 10.55 9 10 9Z" fill="white"/></svg>');

    add_menu_page(
        'AI Takeaway',
        'AI Takeaway',
        'manage_options',
        'sff-ai-takeaway',
        'sff_ai_takeaway_settings_page',
        $icon_svg,
        30
    );
}

// Metabox logic ends
// add_action( 'add_meta_boxes', 'sff_ai_add_preview_metabox' ); // Removed duplicate

function sff_ai_add_preview_metabox() {
    $post_types = get_post_types( array( 'public' => true ), 'names' );
    foreach ( $post_types as $screen ) {
        add_meta_box(
            'sff_ai_takeaway_preview',
            'SFF AI Takeaway - Visual Preview',
            'sff_ai_preview_metabox_callback',
            $screen,
            'normal',
            'high'
        );
    }
}

function sff_ai_preview_metabox_callback( $post ) {
    echo '<div class="sff-ai-metabox-preview" style="background: #fdfdfd; padding: 20px; border: 1px solid #e5e5e5; border-radius: 8px; min-height: 200px;">';
    echo '<div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">';
    echo '<strong style="color:#2271b1;">Previewing:</strong> <span>Shortcode output for this post</span>';
    echo '</div>';
    echo do_shortcode( '[ai_takeaway post_id="' . $post->ID . '"]' );
    echo '</div>';
    
    // Ensure scripts run in the metabox too
    ?>
    <script>
    jQuery(document).ready(function($) {
        if (typeof window.sff_ai_init_chatbot === 'function') {
            window.sff_ai_init_chatbot(<?php echo $post->ID; ?>, 'admin_preview_' + <?php echo $post->ID; ?>, ajaxurl);
        }
    });
    </script>
    <?php
}

/**
 * Settings Page Callback
 */
function sff_ai_takeaway_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Save settings
    if ( isset( $_POST['sff_ai_save_settings'] ) && check_admin_referer( 'sff_ai_settings_nonce' ) ) {
        $settings = array(
            'endpoint'    => sanitize_text_field( $_POST['endpoint'] ),
            'api_key'     => sanitize_text_field( $_POST['api_key'] ),
            'model'        => sanitize_text_field( $_POST['model'] ),
            'model_login'  => sanitize_text_field( $_POST['model_login'] ),
            'model_guest'  => sanitize_text_field( $_POST['model_guest'] ),
            'prompt'       => sanitize_textarea_field( $_POST['prompt'] ),
            'guest_limit'            => intval( $_POST['guest_limit'] ),
            'user_limit'             => intval( $_POST['user_limit'] ),
            'log_retention'          => intval( $_POST['log_retention'] ),
            'article_meta_mappings'  => isset($_POST['meta_mapping']) ? (array)$_POST['meta_mapping'] : array(),
            'user_meta_mappings'     => isset($_POST['user_meta_mapping']) ? (array)$_POST['user_meta_mapping'] : array(),
        );

        // Sanitize article mappings
        foreach($settings['article_meta_mappings'] as &$mapping) {
            $mapping['key'] = sanitize_text_field($mapping['key'] ?? '');
            $mapping['label'] = sanitize_text_field($mapping['label'] ?? '');
            $mapping['type'] = sanitize_text_field($mapping['type'] ?? 'text');
        }

        // Sanitize user mappings
        foreach($settings['user_meta_mappings'] as &$mapping) {
            $mapping['key'] = sanitize_text_field($mapping['key'] ?? '');
            $mapping['label'] = sanitize_text_field($mapping['label'] ?? '');
        }


        update_option( 'sff_ai_takeaway_settings', $settings );

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $settings = get_option( 'sff_ai_takeaway_settings', array() );
    $endpoint = $settings['endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
    $api_key = $settings['api_key'] ?? '';
    $model = $settings['model'] ?? 'google/gemini-2.0-flash-001';
    $model_login = $settings['model_login'] ?? $model;
    $model_guest = $settings['model_guest'] ?? $model;
    $prompt = $settings['prompt'] ?? '';
    $guest_limit = $settings['guest_limit'] ?? 3;
    $user_limit = $settings['user_limit'] ?? 100;
    $log_retention = $settings['log_retention'] ?? 30; // Default 30 days
    $meta_mappings = $settings['article_meta_mappings'] ?? array();
    $user_meta_mappings = $settings['user_meta_mappings'] ?? array();

    // Set defaults if empty
    if ( empty($user_meta_mappings) ) {
        $user_meta_mappings = array(
            array('key' => 'display_name', 'label' => 'Full Name'),
            array('key' => 'user_email', 'label' => 'Email Address'),
            array('key' => '', 'label' => 'University'),
            array('key' => '', 'label' => 'Country'),
        );
    }




    ?>
    <div class="wrap">
        <h1>SFF AI Takeaway Settings</h1>
        <script>
        window.sff_is_admin = true;
        // Global definition for admin preview
        window.sff_ai_init_chatbot = function(postId, chatSessionId, ajaxUrl) {
            var $ = jQuery;
            console.log('Initializing SFF AI Chatbot for Post ID:', postId);
            
            var $chatInput = $('#sff_ai_chat_input_' + postId);
            var $chatSend = $('#sff_ai_chat_send_' + postId);
            var $chatArea = $('#sff_ai_chat_area_' + postId);

            if ($chatArea.length === 0) {
                console.warn('SFF AI Chatbot Area not found for Post ID:', postId);
                return;
            }

            function addMessage(text, side) {
                var $msg = $('<div class="sff-ai-msg"></div>').addClass(side === 'user' ? 'sff-ai-msg-sent' : 'sff-ai-msg-received').text(text);
                $chatArea.append($msg);
                $chatArea.scrollTop($chatArea[0].scrollHeight);
                return $msg;
            }

            function addLoading() {
                var $loader = $('<div class="sff-ai-msg sff-ai-msg-received sff-ai-loading-dots"><span>.</span><span>.</span><span>.</span></div>');
                $chatArea.append($loader);
                $chatArea.scrollTop($chatArea[0].scrollHeight);
                return $loader;
            }

            function sendMessage() {
                var message = $chatInput.val().trim();
                if (!message) return;

                addMessage(message, 'user');
                $chatInput.val('');
                var $loading = addLoading();

                $chatInput.prop('disabled', true);
                $chatSend.prop('disabled', true);

                $.post(ajaxUrl, {
                    action: 'sff_ai_chat',
                    post_id: postId,
                    message: message,
                    chat_session_id: chatSessionId
                }, function(response) {
                    $loading.remove();
                    $chatInput.prop('disabled', false);
                    $chatSend.prop('disabled', false);
                    $chatInput.focus();

                    if (response.success) {
                        addMessage(response.data, 'bot');
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Sorry, something went wrong.';
                        addMessage(errorMsg, 'bot');
                    }
                }).fail(function() {
                    $loading.remove();
                    $chatInput.prop('disabled', false);
                    $chatSend.prop('disabled', false);
                    addMessage('Network error.', 'bot');
                });
            }

            $chatSend.off('click').on('click', sendMessage);
            $chatInput.off('keypress').on('keypress', function(e) {
                if (e.key === 'Enter') sendMessage();
            });

            // --- Typing Animation ---
            var $container = $chatArea.closest('.sff-ai-takeaway-container');
            var $contentEl = $container.find('.sff-ai-takeaway-content');
            
            if ($contentEl.length && !$contentEl.data('typing-started')) {
                $contentEl.data('typing-started', true);
                var fullText = $contentEl.attr('data-text') || "";
                var charIndex = 0;
                var typingSpeed = 10;

                function sffStartTyping() {
                    if (charIndex < fullText.length) {
                        $contentEl.append(fullText.charAt(charIndex));
                        charIndex++;
                        setTimeout(sffStartTyping, typingSpeed);
                    } else {
                        $contentEl.addClass('typing-done');
                        $chatInput.prop('disabled', false);
                        $chatSend.prop('disabled', false);
                    }
                }

                if ('IntersectionObserver' in window && !window.sff_is_admin) {
                    var observer = new IntersectionObserver(function(entries) {
                        if (entries[0].isIntersecting) {
                            sffStartTyping();
                            observer.disconnect();
                        }
                    }, { threshold: 0.2 });
                    observer.observe($contentEl[0]);
                } else {
                    sffStartTyping();
                }
            } else if ($contentEl.hasClass('typing-done')) {
                // Already typed out elements should be enabled
                $chatInput.prop('disabled', false);
                $chatSend.prop('disabled', false);
            }
        };
        </script>
        <form method="post" action="">
            <?php wp_nonce_field( 'sff_ai_settings_nonce' ); ?>
            
            <h2>API Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="endpoint">AI Endpoint URL</label></th>
                    <td><input name="endpoint" type="text" id="endpoint" value="<?php echo esc_attr( $endpoint ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="api_key">API Key</label></th>
                    <td><input name="api_key" type="password" id="api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="model">Default Model Name</label></th>
                    <td><input name="model" type="text" id="model" value="<?php echo esc_attr( $model ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="model_login">Model for Login User</label></th>
                    <td><input name="model_login" type="text" id="model_login" value="<?php echo esc_attr( $model_login ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="model_guest">Model for Non-login User</label></th>
                    <td><input name="model_guest" type="text" id="model_guest" value="<?php echo esc_attr( $model_guest ); ?>" class="regular-text"></td>
                </tr>
            </table>


            <h2>Article Meta Mapping (Dynamic)</h2>
            <p class="description">Define which meta fields from the post should be sent to the AI and how they should be interpreted (e.g. manuscript files, specific text data).</p>
            <table class="wp-list-table widefat fixed striped" id="meta-mapping-table">
                <thead>
                    <tr>
                        <th>Meta Key</th>
                        <th>Label (Context for AI)</th>
                        <th>Type</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($meta_mappings)): ?>
                        <?php foreach($meta_mappings as $idx => $m): ?>
                            <tr>
                                <td><input type="text" name="meta_mapping[<?php echo $idx; ?>][key]" value="<?php echo esc_attr($m['key']); ?>" class="widefat"></td>
                                <td><input type="text" name="meta_mapping[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($m['label']); ?>" class="widefat" placeholder="e.g. Manuscript File"></td>
                                <td>
                                    <select name="meta_mapping[<?php echo $idx; ?>][type]" class="widefat">
                                        <option value="text" <?php selected($m['type'], 'text'); ?>>Text / Content</option>
                                        <option value="file_id" <?php selected($m['type'], 'file_id'); ?>>File (Attachment ID)</option>
                                        <option value="file_url" <?php selected($m['type'], 'file_url'); ?>>File (URL)</option>
                                        <option value="file_binary" <?php selected($m['type'], 'file_binary'); ?>>File (Binary/Attachment)</option>
                                    </select>
                                </td>
                                <td><button type="button" class="button remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><button type="button" class="button" id="add-meta-mapping">Add Meta Field</button></td>
                    </tr>
                </tfoot>
            </table>

            <h2>Author User Meta Mapping (Dynamic)</h2>
            <p class="description">Define which meta fields from the Author (User) should be sent to the AI.</p>
            <table class="wp-list-table widefat fixed striped" id="user-meta-mapping-table">
                <thead>
                    <tr>
                        <th>User Meta Key</th>
                        <th>Label (Context for AI)</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($user_meta_mappings)): ?>
                        <?php foreach($user_meta_mappings as $idx => $m): ?>
                            <tr>
                                <td><input type="text" name="user_meta_mapping[<?php echo $idx; ?>][key]" value="<?php echo esc_attr($m['key']); ?>" class="widefat"></td>
                                <td><input type="text" name="user_meta_mapping[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($m['label']); ?>" class="widefat" placeholder="e.g. Bio / Qualifications"></td>
                                <td><button type="button" class="button remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><button type="button" class="button" id="add-user-meta-mapping">Add User Meta Field</button></td>
                    </tr>
                </tfoot>
            </table>


            <h2>Rate Limiting</h2>
            <p class="description">Set the number of chats allowed per 24 hours.</p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="guest_limit">Guest Daily Limit</label></th>
                    <td><input name="guest_limit" type="number" id="guest_limit" value="<?php echo esc_attr( $guest_limit ); ?>" class="small-text"> </td>
                </tr>
                <tr>
                    <th scope="row"><label for="user_limit">Logged-in User Daily Limit</label></th>
                    <td><input name="user_limit" type="number" id="user_limit" value="<?php echo esc_attr( $user_limit ); ?>" class="small-text"> </td>
                </tr>
                <tr>
                    <th scope="row"><label for="log_retention">Log Retention (Days)</label></th>
                    <td>
                        <input name="log_retention" type="number" id="log_retention" value="<?php echo esc_attr( $log_retention ); ?>" class="small-text">
                        <p class="description">Logs older than this will be automatically deleted. Set to 0 to keep forever.</p>
                    </td>
                </tr>
            </table>


            <h2>AI Prompt & Dynamic Tags</h2>
            <p class="description">
                You can use dynamic tags in your prompt. These will be replaced with actual values from the article.
                <br><strong>Available Tags:</strong> 
                <code>{{post_title}}</code>, <code>{{post_content}}</code>, <code>{{ocr_content}}</code>, 
                <code>{{author_name}}</code>, <code>{{author_university}}</code>, <code>{{author_country}}</code>, <code>{{author_email}}</code>
                <br><strong>Meta Tags:</strong> <code>{{meta:your_meta_key}}</code> (from Post Meta), <code>{{user_meta:your_meta_key}}</code> (from Author User Meta)
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="prompt">System Prompt</label></th>
                    <td>
                        <textarea name="prompt" id="prompt" rows="10" cols="50" class="large-text code" style="background:#f9f9f9;"><?php echo esc_textarea( $prompt ); ?></textarea>
                        <p class="description">This prompt is sent for every NEW chat session to set the AI's behavior.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="sff_ai_save_settings" id="submit" class="button button-primary" value="Save Settings">
            </p>
        </form>

        <hr>

        <div class="sff-ai-tools-container" style="display: flex; gap: 20px;">
            <!-- Left: Meta Explorer -->
            <div style="flex: 1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3>Context Explorer (MetaBox)</h3>
                <p class="description">Enter a Post ID to see all available meta keys and values you can use in your prompt.</p>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <input type="number" id="explore_post_id" placeholder="Post ID" class="small-text">
                    <button type="button" id="sff_explore_meta_btn" class="button">Explore Meta Keys</button>
                </div>
                <div id="sff_explore_results" style="max-height: 400px; overflow-y: auto;">
                    <p class="description">Results will appear here...</p>
                </div>
            </div>

            <!-- Right: AI Takeaway Tester -->
            <div style="flex: 1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3>AI Response Preview</h3>
                <p class="description">Test how the AI responds with your current settings and prompt.</p>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <input type="number" id="test_post_id" placeholder="Post ID" class="small-text">
                    <button type="button" id="sff_test_ai_btn" class="button">Raw Response Test</button>
                    <button type="button" id="sff_preview_ui_btn" class="button button-primary">Visual UI Preview</button>
                </div>
                <div id="sff_test_results" style="max-height: 600px; overflow-y: auto;">
                    <p class="description">Preview will appear here...</p>
                </div>
            </div>
        </div>

    </div>

    <script>
    jQuery(document).ready(function($) {
        // --- Dynamic Meta Mapping ---
        var mappingIdx = <?php echo count($meta_mappings); ?>;
        $('#add-meta-mapping').on('click', function() {
            var row = `<tr>
                <td><input type="text" name="meta_mapping[${mappingIdx}][key]" value="" class="widefat"></td>
                <td><input type="text" name="meta_mapping[${mappingIdx}][label]" value="" class="widefat" placeholder="e.g. Manuscript File"></td>
                <td>
                    <select name="meta_mapping[${mappingIdx}][type]" class="widefat">
                        <option value="text">Text / Content</option>
                        <option value="file_id">File (Attachment ID)</option>
                        <option value="file_url">File (URL)</option>
                        <option value="file_binary">File (Binary/Attachment)</option>
                    </select>
                </td>
                <td><button type="button" class="button remove-row">Remove</button></td>
            </tr>`;
            $('#meta-mapping-table tbody').append(row);
            mappingIdx++;
        });

        // --- Dynamic User Meta Mapping ---
        var userMappingIdx = <?php echo count($user_meta_mappings); ?>;
        $('#add-user-meta-mapping').on('click', function() {
            var row = `<tr>
                <td><input type="text" name="user_meta_mapping[${userMappingIdx}][key]" value="" class="widefat"></td>
                <td><input type="text" name="user_meta_mapping[${userMappingIdx}][label]" value="" class="widefat" placeholder="e.g. Qualifications"></td>
                <td><button type="button" class="button remove-row">Remove</button></td>
            </tr>`;
            $('#user-meta-mapping-table tbody').append(row);
            userMappingIdx++;
        });

        $(document).on('click', '.remove-row', function() {

            $(this).closest('tr').remove();
        });

        // --- Context Explorer (MetaBox style) ---
        $('#sff_explore_meta_btn').on('click', function() {
            var postId = $('#explore_post_id').val();
            if(!postId) return;
            $('#sff_explore_results').html('<p>Exploring context...</p>');
            
            $.post(ajaxurl, {
                action: 'sff_ai_explore_meta',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("sff_ai_test_nonce"); ?>'
            }, function(response) {
                if(response.success) {
                    var data = response.data;
                    var html = `<h4>Article: ${data.post_data.title}</h4>`;
                    
                    html += `<strong>Post Meta:</strong><ul style="font-size:11px; font-family:monospace; background:#f5f5f5; padding:10px; border-radius:4px;">`;
                    $.each(data.post_meta, function(key, val) {
                        html += `<li><strong>{{meta:${key}}}:</strong> ${val[0]}</li>`;
                    });
                    html += `</ul>`;

                    html += `<strong>Author Meta:</strong><ul style="font-size:11px; font-family:monospace; background:#f5f5f5; padding:10px; border-radius:4px;">`;
                    $.each(data.user_meta, function(key, val) {
                        html += `<li><strong>{{user_meta:${key}}}:</strong> ${val[0]}</li>`;
                    });
                    html += `</ul>`;

                    html += `<strong>Related Files:</strong><ul style="font-size:11px; font-family:monospace; background:#f5f5f5; padding:10px; border-radius:4px;">`;
                    $.each(data.files, function(i, f) {
                        html += `<li>${f.name} (<a href="${f.url}" target="_blank">View</a>)</li>`;
                    });
                    html += `</ul>`;

                    $('#sff_explore_results').html(html);
                } else {
                    $('#sff_explore_results').html('<p style="color:red;">Error: '+response.data+'</p>');
                }
            });
        });

        // --- Tester ---
        $('#sff_test_ai_btn').on('click', function() {
            var postId = $('#test_post_id').val();
            if(!postId) { alert('Please enter a Post ID'); return; }
            
            $('#sff_test_results').html('<p>Generating preview...</p>');
            
            $.post(ajaxurl, {
                action: 'sff_ai_test_context',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("sff_ai_test_nonce"); ?>'
            }, function(response) {
                if(response.success) {
                    var html = `<div style="padding:10px; background:#e1f0ff; border-left:4px solid #0073aa; margin-bottom:15px;">
                        <strong>Rendered System Prompt:</strong><br>
                        <pre style="white-space:pre-wrap; font-size:12px;">${response.data.rendered_prompt}</pre>
                    </div>`;

                    html += `<div style="padding:10px; background:#f0f0f0; margin-bottom:15px;">
                        <strong>Final Context Data (JSON):</strong><br>
                        <pre style="font-size:11px; max-height:200px; overflow:auto;">${JSON.stringify(response.data.context, null, 2)}</pre>
                    </div>`;

                    html += `<div style="padding:10px; background:#fff; border:1px solid #ccd0d4;">
                        <strong>AI Response Preview:</strong><br>
                        <div style="margin-top:10px;">${response.data.ai_response}</div>
                    </div>`;

                    $('#sff_test_results').html(html);
                } else {
                    $('#sff_test_results').html('<p style="color:red;">Error: ' + response.data + '</p>');
                }
            });
        });

        // --- Visual UI Preview ---
        $('#sff_preview_ui_btn').on('click', function() {
            var postId = $('#test_post_id').val();
            if(!postId) { alert('Please enter a Post ID'); return; }
            
            $('#sff_test_results').html('<p style="padding:10px; background:#fff3cd; border-left:4px solid #ffc107;">Loading Chatbot UI...</p>');
            
            $.post(ajaxurl, {
                action: 'sff_ai_get_shortcode_preview',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("sff_ai_test_nonce"); ?>'
            }, function(response) {
                if (response === '-1' || response === '0') {
                    $('#sff_test_results').html('<p style="color:red;">Error: Security check failed (Invalid Nonce). Please refresh the page.</p>');
                    return;
                }
                
                $('#sff_test_results').html(response);
                
                // Re-initialize the chatbot script for the newly loaded HTML
                // We retry a few times to ensure the script in the response has been executed
                var retries = 0;
                var initCheck = setInterval(function() {
                    if (typeof window.sff_ai_init_chatbot === 'function') {
                        clearInterval(initCheck);
                        var sessId = 'sff_admin_test_' + Date.now();
                        window.sff_ai_init_chatbot(postId, sessId, ajaxurl);
                    } else if (retries > 20) {
                        clearInterval(initCheck);
                        console.error('sff_ai_init_chatbot function not found after 2 seconds.');
                    }
                    retries++;
                }, 100);
            }).fail(function(xhr, status, error) {
                $('#sff_test_results').html('<p style="color:red;">AJAX Error: ' + error + '</p>');
            });
        });

    });
    </script>
    <?php
}

