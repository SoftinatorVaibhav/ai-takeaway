<?php
/**
 * Admin Settings for GJ AI Takeaway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register Hooks
add_action( 'admin_menu', 'gj_ai_takeaway_menu' );
add_action( 'add_meta_boxes', 'gj_ai_add_preview_metabox' );

function gj_ai_takeaway_menu() {
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16C6.69 16 4 13.31 4 10C4 6.69 6.69 4 10 4C13.31 4 16 6.69 16 10C16 13.31 13.31 16 10 16Z" fill="white"/><path d="M10 6C7.79 6 6 7.79 6 10C6 12.21 7.79 14 10 14C12.21 14 14 12.21 14 10C14 7.79 12.21 6 10 6ZM10 12C8.9 12 8 11.1 8 10C8 8.9 8.9 8 10 8C11.1 8 12 8.9 12 10C12 11.1 11.1 12 10 12Z" fill="white"/><path d="M10 9C9.45 9 9 9.45 9 10C9 10.55 9.45 11 10 11C10.55 11 11 10.55 11 10C11 9.45 10.55 9 10 9Z" fill="white"/></svg>');

    add_menu_page(
        'AI Takeaway',
        'AI Takeaway',
        'manage_options',
        'gj-ai-takeaway',
        'gj_ai_takeaway_settings_page',
        $icon_svg,
        30
    );
}

// Add Metabox to Posts/Pages
add_action( 'add_meta_boxes', 'gj_ai_add_preview_metabox' );

function gj_ai_add_preview_metabox() {
    $post_types = get_post_types( array( 'public' => true ), 'names' );
    foreach ( $post_types as $screen ) {
        add_meta_box(
            'gj_ai_takeaway_preview',
            'GJ AI Takeaway - Visual Preview',
            'gj_ai_preview_metabox_callback',
            $screen,
            'normal',
            'high'
        );
    }
}

function gj_ai_preview_metabox_callback( $post ) {
    echo '<div class="gj-ai-metabox-preview" style="background: #fdfdfd; padding: 20px; border: 1px solid #e5e5e5; border-radius: 8px; min-height: 200px;">';
    echo '<div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">';
    echo '<strong style="color:#2271b1;">Previewing:</strong> <span>Shortcode output for this post</span>';
    echo '</div>';
    echo do_shortcode( '[ai_takeaway post_id="' . $post->ID . '"]' );
    echo '</div>';
    
    // Ensure scripts run in the metabox too
    ?>
    <script>
    jQuery(document).ready(function($) {
        if (typeof window.gj_ai_init_chatbot === 'function') {
            window.gj_ai_init_chatbot(<?php echo $post->ID; ?>, 'admin_preview_' + <?php echo $post->ID; ?>, ajaxurl);
        }
    });
    </script>
    <?php
}

/**
 * Settings Page Callback
 */
function gj_ai_takeaway_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Save settings
    if ( isset( $_POST['gj_ai_save_settings'] ) && check_admin_referer( 'gj_ai_settings_nonce' ) ) {
        $settings = array(
            'endpoint'    => sanitize_text_field( $_POST['endpoint'] ),
            'api_key'     => sanitize_text_field( $_POST['api_key'] ),
            'model'        => sanitize_text_field( $_POST['model'] ),
            'prompt'       => sanitize_textarea_field( $_POST['prompt'] ),
            'guest_limit'            => intval( $_POST['guest_limit'] ),
            'user_limit'             => intval( $_POST['user_limit'] ),
            'log_retention'          => intval( $_POST['log_retention'] ),
            'article_meta_mappings'  => array_values($_POST['meta_mapping'] ?? array()),
            'user_meta_mappings'     => array_values($_POST['user_meta_mapping'] ?? array()),
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


        update_option( 'gj_ai_takeaway_settings', $settings );

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $settings = get_option( 'gj_ai_takeaway_settings', array() );
    $endpoint = $settings['endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
    $api_key = $settings['api_key'] ?? '';
    $model = $settings['model'] ?? 'google/gemini-2.0-flash-001';
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
        <h1>GJ AI Takeaway Settings</h1>
        <script>window.gj_is_admin = true;</script>
        <form method="post" action="">
            <?php wp_nonce_field( 'gj_ai_settings_nonce' ); ?>
            
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
                    <th scope="row"><label for="model">Model Name</label></th>
                    <td><input name="model" type="text" id="model" value="<?php echo esc_attr( $model ); ?>" class="regular-text"></td>
                </tr>
            </table>

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
                <code>{{post_title}}</code>, <code>{{post_content}}</code>, 
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
                <input type="submit" name="gj_ai_save_settings" id="submit" class="button button-primary" value="Save Settings">
            </p>
        </form>

        <hr>

        <div class="gj-ai-tools-container" style="display: flex; gap: 20px;">
            <!-- Left: Meta Explorer -->
            <div style="flex: 1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3>Context Explorer (MetaBox)</h3>
                <p class="description">Enter a Post ID to see all available meta keys and values you can use in your prompt.</p>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <input type="number" id="explore_post_id" placeholder="Post ID" class="small-text">
                    <button type="button" id="gj_explore_meta_btn" class="button">Explore Meta Keys</button>
                </div>
                <div id="gj_explore_results" style="max-height: 400px; overflow-y: auto;">
                    <p class="description">Results will appear here...</p>
                </div>
            </div>

            <!-- Right: AI Takeaway Tester -->
            <div style="flex: 1; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3>AI Response Preview</h3>
                <p class="description">Test how the AI responds with your current settings and prompt.</p>
                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <input type="number" id="test_post_id" placeholder="Post ID" class="small-text">
                    <button type="button" id="gj_test_ai_btn" class="button">Raw Response Test</button>
                    <button type="button" id="gj_preview_ui_btn" class="button button-primary">Visual UI Preview</button>
                </div>
                <div id="gj_test_results" style="max-height: 600px; overflow-y: auto;">
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
        $('#gj_explore_meta_btn').on('click', function() {
            var postId = $('#explore_post_id').val();
            if(!postId) return;
            $('#gj_explore_results').html('<p>Exploring context...</p>');
            
            $.post(ajaxurl, {
                action: 'gj_ai_explore_meta',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("gj_ai_test_nonce"); ?>'
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

                    $('#gj_explore_results').html(html);
                } else {
                    $('#gj_explore_results').html('<p style="color:red;">Error: '+response.data+'</p>');
                }
            });
        });

        // --- Tester ---
        $('#gj_test_ai_btn').on('click', function() {
            var postId = $('#test_post_id').val();
            if(!postId) { alert('Please enter a Post ID'); return; }
            
            $('#gj_test_results').html('<p>Generating preview...</p>');
            
            $.post(ajaxurl, {
                action: 'gj_ai_test_context',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("gj_ai_test_nonce"); ?>'
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

                    $('#gj_test_results').html(html);
                } else {
                    $('#gj_test_results').html('<p style="color:red;">Error: ' + response.data + '</p>');
                }
            });
        });

        // --- Visual UI Preview ---
        $('#gj_preview_ui_btn').on('click', function() {
            var postId = $('#test_post_id').val();
            if(!postId) { alert('Please enter a Post ID'); return; }
            
            $('#gj_test_results').html('<p>Loading Chatbot UI...</p>');
            
            $.post(ajaxurl, {
                action: 'gj_ai_get_shortcode_preview',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce("gj_ai_test_nonce"); ?>'
            }, function(response) {
                $('#gj_test_results').html(response);
                
                // Re-initialize the chatbot script for the newly loaded HTML
                if (typeof window.gj_ai_init_chatbot === 'function') {
                    var sessId = 'gj_admin_test_' + Date.now();
                    window.gj_ai_init_chatbot(postId, sessId, ajaxurl);
                }
            });
        });

    });
    </script>
    <?php
}
