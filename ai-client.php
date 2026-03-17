<?php
/**
 * AI Client for GJ AI Takeaway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GJ_AI_Client {
    private $endpoint;
    private $api_key;
    private $model;

    public function __construct( $model = null ) {
        $settings = get_option( 'gj_ai_takeaway_settings', array() );
        $this->endpoint = $settings['endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
        $this->api_key  = $settings['api_key'] ?? '';
        
        if ( $model ) {
            $this->model = $model;
        } else {
            $this->model = $settings['model'] ?? 'google/gemini-2.0-flash-001';
        }
    }

    public function get_response( $messages ) {
        if ( empty( $this->api_key ) ) {
            return "Error: API Key is missing in settings.";
        }

        $body = array(
            'model'    => $this->model,
            'messages' => $messages,
        );

        $response = wp_remote_post( $this->endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => home_url(), 
                'X-Title'       => 'GJ AI Takeaway WP Plugin',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return "Error: " . $response->get_error_message();
        }

        $res_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $res_body, true );

        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return $data['choices'][0]['message']['content'];
        }

        return "Error: AI response format invalid. " . $res_body;
    }
}
