# GJ AI Takeaway 🧠

**GJ AI Takeaway** is a premium WordPress plugin that integrates a state-of-the-art AI chatbot into your articles. It doesn't just "chat"; it connects deeply with your content by gathering context from post meta, author details, and "Eternal Ground" information to provide highly accurate, article-specific answers.

---

## ✨ Key Features

- **Dual Model Support**: Configure separate AI models for Logged-in Users vs. Guests (e.g., use a powerful model for subscribers and a cost-effective one for guests).
- **Automatic OCR Manuscript Retrieval**: Automatically fetches `final.md` from the "Wasabi Manuscript" private bucket (via GJ Doc lifecycle) and attaches it as a primary document for AI analysis.
- **Multimodal Binary Attachments**: Support for sending actual file content (PDFs, Images, etc.) to the AI in binary/multimodal format, allowing the AI to "see" your manuscript or related data.
- **Dynamic Typing Animation**: The "AI Takeaway" summary types out word-by-word when the user scrolls to it, creating a "live" feel.
- **Eternal Ground Connectivity**: A unique meta-layer that allows you to provide "eternal" context to the AI, ensuring it knows the deep background of your topic.
- **Interactive Chat Interface**: A sleek, gradient-styled chatbot that lives right inside your article template.
- **Session Intelligence**: Uses browser `sessionStorage` to keep conversations alive and WordPress transients to persist chat history server-side.
- **Dynamic Context Mapping**: Configure exactly which Post Meta and User Meta fields are sent to the AI, with support for Text, File IDs, and Binary Attachments.
- **Smart Rate Limiting**: Separate daily chat limits for Guests vs. Logged-in Users to prevent API abuse.
- **Interaction Logs**: Built-in dashboard to monitor user queries and AI responses for quality control.

---

## 🚀 Quick Start

### 1. Installation
1. Ensure you have the plugin files in `wp-content/plugins/ai-takeaway-main/`.
2. Activate the plugin through the WordPress 'Plugins' menu.

### 2. Configuration
1. Navigate to **AI Takeaway > Settings**.
2. Enter your **AI Endpoint URL** (Supports OpenRouter, OpenAI, etc.).
3. Enter your **API Key**.
4. Set your **Models**: Define specific models for Login Users and Non-login users.
5. Set your **System Prompt**. Use tags like `{{post_title}}`, `{{post_content}}`, and `{{ocr_content}}` to define how the AI behaves.

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
| `{{ocr_content}}` | The full text/markdown content fetched from the Wasabi OCR manuscript. |
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
- **Key Function**: `gj_ai_perform_log_cleanup()` - Handles the automated removal of old logs.

### 2. `shortcode.php` (UI/UX)
- **Responsibility**: Renders the frontend chatbot interface and the "Typing Animation."
- **Key Function**: `gj_ai_takeaway_shortcode()` - The primary engine for the `[ai_takeaway]` shortcode.

### 3. `admin-settings.php` (Configuration)
- **Responsibility**: Handles the global settings page, dual-model configuration, and dynamic meta-mapping interface.
- **Key Function**: `gj_ai_add_preview_metabox()` - Registers the live simulator on the post-edit screen.

### 4. `ajax-handlers.php` (Real-time Processing)
- **Responsibility**: Manages background communication, multimodal message construction, and context gathering.
- **Key Function**: `gj_ai_chat_handler()` - Processes messages and handles session-based history.
- **Helper**: `gj_ai_gather_post_context()` - Fetches meta, author data, and OCR manuscripts.

### 5. `ai-client.php` (Connectivity)
- **Responsibility**: Pure API wrapper for sending standard and multimodal requests to the configured AI endpoint.

### 6. `dashboard.php` (Analytics & Logs)
- **Responsibility**: Provides the interaction log table with filtering and deletion tools.

---

## 🛠️ Security & Performance

- **Multimodal Guard**: Only attaches heavy binary data (OCR, files) **once** per session (on the first message) to minimize API latency and token costs.
- **Scoped Assets**: JavaScript and CSS are injected **only** on required pages.
- **Nonces & Transients**: All AJAX calls are secured with WordPress Nonces, and rate limits are enforced via memory-efficient transients.
- **MIME Verification**: Binary attachments are validated for MIME type before being sent to the AI.

---

## 🔧 Technical Details

- **Database**: Custom table `wp_gj_ai_logs` tracks interactions.
- **Rate Limits**: IP-based transients for guests; User-ID based for logged-in users.
- **Styling**: Vanilla CSS with modern gradients and responsive layout.
- **Integration**: Deeply integrates with the Wasabi Markdown Article plugin ecosystem for OCR support.

---

## 🛠️ Developer Tools

The plugin includes diagnostic tools in the settings page:
- **Context Explorer**: Peek into the metadata of any post to see exactly what the AI will "see."
- **Response Preview**: Test the AI integration and multimodal responses for a specific post.

---

*Connecting your content with the Eternal Ground.*
