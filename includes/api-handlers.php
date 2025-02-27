<?php
/**
 * API Handlers for different LLM providers
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process chat request and route to appropriate API handler
 */
function easyai_chat_process_request($request) {
    $message = sanitize_text_field($request->get_param('message'));
    $options = get_option('easyai_chat_options');
    
    if (empty($message)) {
        return new WP_REST_Response(array(
            'response' => 'Please provide a message.',
            'error' => true
        ), 200);
    }
    
    // Route to the appropriate API handler based on provider
    switch ($options['llm_provider']) {
        case 'openai':
            return easyai_chat_openai_request($message, $options);
        case 'anthropic':
            return easyai_chat_anthropic_request($message, $options);
        case 'gemini':
            return easyai_chat_gemini_request($message, $options);
        default:
            return new WP_REST_Response(array(
                'response' => 'Invalid LLM provider configured.',
                'error' => true
            ), 200);
    }
}

/**
 * Process OpenAI (ChatGPT) API request
 */
function easyai_chat_openai_request($message, $options) {
    if (empty($options['api_key'])) {
        return new WP_REST_Response(array(
            'response' => 'OpenAI API key not configured.',
            'error' => true
        ), 200);
    }
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $options['api_key'],
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'model' => 'gpt-4',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $options['system_prompt']
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => intval($options['max_tokens']),
            'temperature' => floatval($options['temperature'])
        )),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return new WP_REST_Response(array(
            'response' => 'Error: ' . $response->get_error_message(),
            'error' => true
        ), 200);
    }
    
    $body = json_decode(wp_remote_retrieve_body($response));
    
    if (isset($body->error)) {
        return new WP_REST_Response(array(
            'response' => 'API Error: ' . $body->error->message,
            'error' => true
        ), 200);
    }
    
    if (!isset($body->choices[0]->message->content)) {
        return new WP_REST_Response(array(
            'response' => 'Unexpected response format from OpenAI',
            'error' => true
        ), 200);
    }
    
    return new WP_REST_Response(array(
        'response' => $body->choices[0]->message->content,
        'error' => false
    ), 200);
}

/**
 * Process Anthropic (Claude) API request
 */
function easyai_chat_anthropic_request($message, $options) {
    if (empty($options['api_key'])) {
        return new WP_REST_Response(array(
            'response' => 'Anthropic API key not configured.',
            'error' => true
        ), 200);
    }
    
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
        'headers' => array(
            'x-api-key' => $options['api_key'],
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'model' => 'claude-3-opus-20240229',
            'system' => $options['system_prompt'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => intval($options['max_tokens'])
        )),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return new WP_REST_Response(array(
            'response' => 'Error: ' . $response->get_error_message(),
            'error' => true
        ), 200);
    }
    
    $body = json_decode(wp_remote_retrieve_body($response));
    
    if (isset($body->error)) {
        return new WP_REST_Response(array(
            'response' => 'API Error: ' . $body->error->message,
            'error' => true
        ), 200);
    }
    
    if (!isset($body->content[0]->text)) {
        return new WP_REST_Response(array(
            'response' => 'Unexpected response format from Anthropic',
            'error' => true
        ), 200);
    }
    
    return new WP_REST_Response(array(
        'response' => $body->content[0]->text,
        'error' => false
    ), 200);
}

/**
 * Process Google (Gemini) API request
 */
function easyai_chat_gemini_request($message, $options) {
    if (empty($options['api_key'])) {
        return new WP_REST_Response(array(
            'response' => 'Gemini API key not configured.',
            'error' => true
        ), 200);
    }
    
    $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $options['api_key'], array(
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(
                        array(
                            'text' => $options['system_prompt'] . "\n\n" . $message
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'maxOutputTokens' => intval($options['max_tokens']),
                'temperature' => floatval($options['temperature'])
            )
        )),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return new WP_REST_Response(array(
            'response' => 'Error: ' . $response->get_error_message(),
            'error' => true
        ), 200);
    }
    
    $body = json_decode(wp_remote_retrieve_body($response));
    
    if (isset($body->error)) {
        return new WP_REST_Response(array(
            'response' => 'API Error: ' . $body->error->message,
            'error' => true
        ), 200);
    }
    
    if (!isset($body->candidates[0]->content->parts[0]->text)) {
        return new WP_REST_Response(array(
            'response' => 'Unexpected response format from Gemini',
            'error' => true
        ), 200);
    }
    
    return new WP_REST_Response(array(
        'response' => $body->candidates[0]->content->parts[0]->text,
        'error' => false
    ), 200);
}