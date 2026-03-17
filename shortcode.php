<?php
// File: ai-takeaway.php

// AI Takeaway Shortcode for Article Page
function gj_ai_takeaway_shortcode($atts) {
    // Shortcode attributes
    $atts = shortcode_atts(
        array(
            'post_id' => get_the_ID(),
        ),
        $atts,
        'ai_takeaway'
    );

    $post_id = $atts['post_id'];
    
    // Get Pods field data
    $ai_takeaway_data = '';
    
    // Try using Pods if active
    if (function_exists('pods') && !empty($post_id)) {
        $post_type = get_post_type($post_id);
        $pod = pods($post_type, $post_id);
        if ($pod && $pod->exists()) {
            $ai_takeaway_data = $pod->field('ai_takeaway');
        }
    }
    
    // Fallback to get_post_meta
    if (empty($ai_takeaway_data)) {
        $ai_takeaway_data = get_post_meta($post_id, 'ai_takeaway', true);
    }
    
    // Fallback text if empty
    if (empty($ai_takeaway_data)) {
        $ai_takeaway_data = "The objective of our study was to evaluate, in a population of Togolese People Living With HIV(PLWHIV), the agreement between three scores derived from the general population namely the Framingham score, the Systematic Coronary Risk Evaluation (SCORE), the evaluation of the cardiovascular risk (CVR) according to the World Health Organization.";
    }

    ob_start();
    ?>
    <style>
        .gj-ai-takeaway-container {
            position: relative;
            border: 1.5px solid transparent;
            border-radius: 12px;
            background: linear-gradient(#faf4fd, #f9f7fd) padding-box, linear-gradient(to right, #AB11D5, #4F31C8) border-box;
            padding: 24px 30px;
            font-family: inherit;
            margin: 0px 0px 40px 0px;
            box-sizing: border-box;
            width: 100% ;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .gj-ai-takeaway-header h3 {
            margin: 0 0 15px 0;
            font-size: 36px;
            font-weight: 500;
            color: #1a1a24;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .gj-ai-takeaway-content {
            color: #888;
            font-size: 20px;
            line-height: 1.6;
            margin-bottom: 30px;
            min-height: 1.6em; /* Prevent layout jump */
        }
        .gj-ai-takeaway-content::after {
            content: "";
            border-right: 3px solid #AB11D5;
            margin-left: 2px;
            animation: gjAiBlink 0.8s infinite;
        }
        .gj-ai-takeaway-content.typing-done::after {
            display: none;
        }
        @keyframes gjAiBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .gj-ai-chat-area {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .gj-ai-msg {
            padding: 12px 20px;
            border-radius: 20px;
            max-width: 85%;
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
            animation: gjAiFadeIn 0.3s ease-in-out;
        }
        .gj-ai-msg-received {
            align-self: flex-start;
            background-color: #ededed;
            color: #444;
            border-bottom-left-radius: 4px;
        }
        .gj-ai-msg-sent {
            align-self: flex-end;
            background-color: #f1ecf5;
            color: #333;
            border-bottom-right-radius: 4px;
        }
        .gj-ai-input-container {
            border: 1.5px solid transparent;
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            background: linear-gradient(#efe9f9, #efe9f9) padding-box, linear-gradient(to right, #AB11D5, #4F31C8) border-box;
        }
        .gj-ai-input-container input {
            flex: 1;
            border: none !important;
            background: transparent !important;
            outline: none !important;
            font-size: 14px;
            color: #333;
            box-shadow: none !important;
            padding: 8px 0;
            margin: 0;
        }
        .gj-ai-input-container input::placeholder {
            color: #a9a9a9;
        }
        .gj-ai-input-container button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            transition: opacity 0.2s;
        }
        .gj-ai-input-container button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @keyframes gjAiFadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .gj-ai-loading-dots span {
            animation: gjAiDots 1.4s infinite both;
            font-size: 20px;
            margin: 0 1px;
        }
        .gj-ai-loading-dots span:nth-child(2) { animation-delay: .2s; }
        .gj-ai-loading-dots span:nth-child(3) { animation-delay: .4s; }
        @keyframes gjAiDots {
            0%, 80%, 100% { opacity: 0; }
            40% { opacity: 1; }
        }
    </style>

    <div class="gj-ai-takeaway-container">
        <div class="gj-ai-takeaway-header">
            <h3>AI TAKEAWAY</h3>
        </div>

        <div class="gj-ai-takeaway-content" data-text="<?php echo esc_attr($ai_takeaway_data); ?>">
            <!-- Typing animation handled via JS -->
        </div>

        <div class="gj-ai-chat-area" id="gj_ai_chat_area_<?php echo esc_attr($post_id); ?>">
        </div>

        <div class="gj-ai-input-container">
            <input type="text" id="gj_ai_chat_input_<?php echo esc_attr($post_id); ?>" placeholder="Ask Anything" autocomplete="off" disabled />
            <button type="button" id="gj_ai_chat_send_<?php echo esc_attr($post_id); ?>" aria-label="Send Message" disabled>
                <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.4809 8.9516L22.5084 4.94243C27.9059 3.14327 30.8384 6.08993 29.0534 11.4874L25.0443 23.5149C22.3526 31.6041 17.9326 31.6041 15.2409 23.5149L14.0509 19.9449L10.4809 18.7549C2.39177 16.0633 2.39177 11.6574 10.4809 8.9516Z" stroke="#292D32" stroke-width="2.125" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.3203 19.3358L19.392 14.25" stroke="#292D32" stroke-width="2.125" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div style="font-size: 10px; color: #ccc; text-align: right; margin-top: 5px; font-style: italic;">
            Connecting with the Eternal Ground
        </div>
    </div>

    <script>
    (function($) {
        // Global-ish function to initialize or re-initialize a chatbot instance
        window.gj_ai_init_chatbot = function(postId, chatSessionId, ajaxUrl) {
            var $chatInput = $('#gj_ai_chat_input_' + postId);
            var $chatSend = $('#gj_ai_chat_send_' + postId);
            var $chatArea = $('#gj_ai_chat_area_' + postId);

            function addMessage(text, side) {
                var $msg = $('<div class="gj-ai-msg"></div>').addClass(side === 'user' ? 'gj-ai-msg-sent' : 'gj-ai-msg-received').text(text);
                $chatArea.append($msg);
                $chatArea.scrollTop($chatArea[0].scrollHeight);
                return $msg;
            }

            function addLoading() {
                var $loader = $('<div class="gj-ai-msg gj-ai-msg-received gj-ai-loading-dots"><span>.</span><span>.</span><span>.</span></div>');
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
                    action: 'gj_ai_chat',
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
            var $container = $('#gj_ai_chat_area_' + postId).closest('.gj-ai-takeaway-container');
            var $contentEl = $container.find('.gj-ai-takeaway-content');
            
            if ($contentEl.length && !$contentEl.data('typing-started')) {
                $contentEl.data('typing-started', true);
                var fullText = $contentEl.attr('data-text') || "";
                var charIndex = 0;
                var typingSpeed = 10;

                function gjStartTyping() {
                    if (charIndex < fullText.length) {
                        $contentEl.append(fullText.charAt(charIndex));
                        charIndex++;
                        setTimeout(gjStartTyping, typingSpeed);
                    } else {
                        $contentEl.addClass('typing-done');
                        $chatInput.prop('disabled', false);
                        $chatSend.prop('disabled', false);
                    }
                }

                if ('IntersectionObserver' in window && !window.gj_is_admin) {
                    var observer = new IntersectionObserver(function(entries) {
                        if (entries[0].isIntersecting) {
                            gjStartTyping();
                            observer.disconnect();
                        }
                    }, { threshold: 0.2 });
                    observer.observe($contentEl[0]);
                } else {
                    gjStartTyping();
                }
            }
        };

        $(document).ready(function() {
            var postId = '<?php echo esc_js($post_id); ?>';
            var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
            var sessKey = 'gj_ai_sess_' + postId;
            var chatSessionId = sessionStorage.getItem(sessKey);
            if (!chatSessionId) {
                chatSessionId = 'gj_sess_' + Math.random().toString(36).substring(2, 15) + Date.now();
                sessionStorage.setItem(sessKey, chatSessionId);
            }

            window.gj_ai_init_chatbot(postId, chatSessionId, ajaxUrl);
        });
    })(jQuery);
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('ai_takeaway', 'gj_ai_takeaway_shortcode');
