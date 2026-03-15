# GJ AI Takeaway 🧠

**GJ AI Takeaway** is a premium WordPress plugin that integrates a state-of-the-art AI chatbot into your articles. It doesn't just "chat"; it connects deeply with your content by gathering context from post meta, author details, and "Eternal Ground" information to provide highly accurate, article-specific answers.

---

## ✨ Key Features

- **Dynamic Typing Animation**: The "AI Takeaway" summary types out word-by-word when the user scrolls to it, creating a "live" feel.
- **Eternal Ground Connectivity**: A unique meta-layer that allows you to provide "eternal" context to the AI, ensuring it knows the deep background of your topic.
- **Interactive Chat Interface**: A sleek, gradient-styled chatbot that lives right inside your article template.
- **Session Intelligence**: Uses browser `sessionStorage` to keep conversations alive even if the user refreshes the page.
- **Dynamic Context Mapping**: Configure exactly which Post Meta and User Meta fields are sent to the AI via a visual interface in the admin settings.
- **Smart Rate Limiting**: Separate daily chat limits for Guests vs. Logged-in Users to prevent API abuse.
- **Interaction Logs**: Built-in dashboard to monitor user queries and AI responses for quality control.

---

## 🚀 Quick Start

### 1. Installation
1. Ensure you have the plugin files in `wp-content/plugins/gj-ai-takeaway/`.
2. Activate the plugin through the WordPress 'Plugins' menu.

### 2. Configuration
1. Navigate to **AI Takeaway > Settings**.
2. Enter your **AI Endpoint URL** (Supports OpenRouter, OpenAI, etc.).
3. Enter your **API Key**.
4. Set your **System Prompt**. Use tags like `{{post_title}}`, `{{post_content}}`, and `{{eternal_ground}}` to define how the AI behaves.

### 3. Usage
Add the shortcode to your post content or your Voxel/Elementor article template:
```bbcode
[ai_takeaway]
```

---

## 🏷️ Dynamic Tags

You can use these tags in your **System Prompt** setting:

| Tag | Description |
| :--- | :--- |
| `{{post_title}}` | The title of the current article. |
| `{{post_content}}` | The full (stripped) content of the article. |
| `{{author_name}}` | Display name of the article's author. |
| `{{eternal_ground}}` | Content from the `eternal_ground` post meta field. |
| `{{meta:key}}` | Any custom post meta value (e.g., `{{meta:my_custom_field}}`). |
| `{{user_meta:key}}` | Any custom user meta value from the author. |
| `{{site_url}}` | The base URL of your website. |

---

## 📂 Codebase Structure

The plugin is designed with a modular architecture to ensure high performance and easy maintenance:

### 1. `gj-ai-takeaway.php` (Core)
- **Responsibility**: Main entry point, plugin activation/deactivation, and scheduled maintenance (Cron).
- **Key Function**: `gj_ai_perform_log_cleanup()` - Mathematically handles the automated removal of old logs based on retention settings.

### 2. `shortcode.php` (UI/UX)
- **Responsibility**: Renders the frontend chatbot interface and the "Typing Animation."
- **Key Function**: `gj_ai_takeaway_shortcode()` - The primary engine for the `[ai_takeaway]` shortcode.
- **Isolation**: All CSS and JS are locally scoped to the chatbot container to prevent conflicts with your theme.

### 3. `admin-settings.php` (Configuration)
- **Responsibility**: Handles the global settings page, dynamic meta-mapping interface, and the **Visual Preview** metabox.
- **Key Function**: `gj_ai_add_preview_metabox()` - Registers the live simulator on the post-edit screen.
- **Isolation**: Admin scripts are only executed within the specific plugin pages to ensure zero impact on the rest of the WordPress dashboard.

### 4. `ajax-handlers.php` (Real-time Processing)
- **Responsibility**: Manages all background communication between the browser and the AI API.
- **Key Function**: `gj_ai_chat_handler()` - Processes messages, gathers context, and streams the AI's response safely.
- **Helper**: `gj_ai_gather_post_context()` - The "brain" that collects all mapped metadata before sending it to the AI.

### 5. `ai-client.php` (Connectivity)
- **Responsibility**: Pure API wrapper for sending requests to the configured AI endpoint.
- **Key Function**: `gj_ai_send_request()` - Standardizes the JSON payload for various LLM providers (OpenRouter, Gemini, etc.).

### 6. `dashboard.php` (Analytics & Logs)
- **Responsibility**: Provides the interaction log table with advanced filtering and deletion tools.
- **Key Function**: `gj_ai_takeaway_logs_page()` - Master control for the log interface and manual data pruning.

---

## 🛠️ Security & Isolation

- **Scoped Assets**: JavaScript and CSS are injected **only** on the pages where they are needed (the settings page, the post editor, or where a shortcode exists). This prevents "bloat" and keeps the rest of your site running fast.
- **Nonces & Transients**: All AJAX calls are secured with WordPress Nonces, and rate limits are enforced via memory-efficient transients.

---

## 🔧 Technical Details

- **Database**: Creates a custom table `wp_gj_ai_logs` for tracking interactions.
- **AJAX**: Handles chats asynchronously via the `gj_ai_chat` action.
- **Styling**: Vanilla CSS with modern gradients and micro-animations.
- **Security**: Implements Nonces for admin tools and transients for IP-based rate limiting.

---

## 🛠️ Developer Tools

The plugin includes built-in diagnostic tools in the settings page:
- **Context Explorer**: Peek into the metadata of any post to see exactly what the AI will "see."
- **Response Preview**: Test-run the AI integration for a specific post without leaving the admin panel.

---

*Connecting your content with the Eternal Ground.*
