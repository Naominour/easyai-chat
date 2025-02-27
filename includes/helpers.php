<?php
/**
 * Helper functions for EasyAI-Chat
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get valid icon names
 * 
 * @return array Array of valid icon names
 */
function easyai_chat_get_icons() {
    return array(
        'chat' => __('Chat', 'easyai-chat'),
        'message' => __('Message', 'easyai-chat'),
        'help' => __('Help', 'easyai-chat'),
    );
}

/**
 * Get valid LLM providers
 * 
 * @return array Array of valid LLM providers
 */
function easyai_chat_get_providers() {
    return array(
        'openai' => __('OpenAI (ChatGPT)', 'easyai-chat'),
        'anthropic' => __('Anthropic (Claude)', 'easyai-chat'),
        'gemini' => __('Google (Gemini)', 'easyai-chat'),
    );
}

/**
 * Get default system prompt
 * 
 * @return string Default system prompt
 */
function easyai_chat_get_default_prompt() {
    return __('You are a helpful AI assistant. Provide concise and accurate responses to user questions.', 'easyai-chat');
}

/**
 * Get chat widget template
 * 
 * @return string HTML template for chat widget
 */
function easyai_chat_get_template() {
    ob_start();
    ?>
    <div id="easyai-chat-root"></div>
    <?php
    return ob_get_clean();
}

/**
 * Sanitize options before saving
 * 
 * @param array $options Options to sanitize
 * @return array Sanitized options
 */
function easyai_chat_sanitize_options($options) {
    $sanitized = array();
    
    // Sanitize API key
    $sanitized['api_key'] = sanitize_text_field($options['api_key']);
    
    // Sanitize system prompt
    $sanitized['system_prompt'] = sanitize_textarea_field($options['system_prompt']);
    
    // Sanitize LLM provider
    $providers = array_keys(easyai_chat_get_providers());
    $sanitized['llm_provider'] = in_array($options['llm_provider'], $providers) 
        ? $options['llm_provider'] 
        : 'openai';
    
    // Sanitize numeric values
    $sanitized['max_tokens'] = absint($options['max_tokens']);
    $sanitized['allowed_questions'] = absint($options['allowed_questions']);
    
    // Ensure min/max values for numeric fields
    $sanitized['max_tokens'] = max(50, min(2000, $sanitized['max_tokens']));
    $sanitized['allowed_questions'] = max(1, min(10, $sanitized['allowed_questions']));
    
    // Sanitize temperature (0.0 to 1.0)
    $sanitized['temperature'] = min(1.0, max(0.0, floatval($options['temperature'])));
    
    // Sanitize URLs
    $sanitized['app_store_url'] = esc_url_raw($options['app_store_url']);
    
    // Sanitize text fields
    $sanitized['promotion_message'] = sanitize_text_field($options['promotion_message'] ?? 'Want more insights? Download our app!');
    $sanitized['chat_title'] = sanitize_text_field($options['chat_title'] ?? 'EasyAI Chat');
    $sanitized['chat_subtitle'] = sanitize_text_field($options['chat_subtitle'] ?? 'Ask me anything!');
    
    // Sanitize display options
    $sanitized['display_type'] = in_array($options['display_type'], array('inline', 'popup')) 
        ? $options['display_type'] 
        : 'inline';
    
    $sanitized['position'] = in_array($options['position'], array('bottom-right', 'bottom-left')) 
        ? $options['position'] 
        : 'bottom-right';
    
    // Sanitize color value
    $sanitized['theme_color'] = sanitize_hex_color($options['theme_color']);
    
    // Sanitize button icon
    $icons = array_keys(easyai_chat_get_icons());
    $sanitized['button_icon'] = in_array($options['button_icon'], $icons) 
        ? $options['button_icon'] 
        : 'chat';
    
    // Sanitize example questions
    $sanitized['example_questions'] = array();
    if (isset($options['example_questions']) && is_array($options['example_questions'])) {
        foreach ($options['example_questions'] as $question) {
            if (!empty($question)) {
                $sanitized['example_questions'][] = sanitize_text_field($question);
            }
        }
    }

    // Sanitize limit message and button
    $sanitized['limit_message'] = sanitize_textarea_field($options['limit_message'] ?? 'You\'ve reached the maximum number of questions. Click the button below to continue.');
    $sanitized['limit_button_text'] = sanitize_text_field($options['limit_button_text'] ?? 'Continue');
    $sanitized['limit_button_url'] = esc_url_raw($options['limit_button_url'] ?? '');

    return $sanitized;
}

/**
 * Log debug information if debugging is enabled
 * 
 * @param mixed $data Data to log
 * @param string $title Optional title for the log entry
 */
function easyai_chat_debug_log($data, $title = '') {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        // Format the data
        if (is_array($data) || is_object($data)) {
            $formatted_data = print_r($data, true);
        } else {
            $formatted_data = $data;
        }
        
        // Add title if provided
        if (!empty($title)) {
            $formatted_data = "=== {$title} ===\n{$formatted_data}\n";
        }
        
        // Log the data
        error_log($formatted_data);
    }
}

/**
 * Check if API key is valid
 * 
 * @param string $api_key API key to check
 * @param string $provider Provider to check against
 * @return bool|string True if valid, error message if not
 */
function easyai_chat_check_api_key($api_key, $provider) {
    if (empty($api_key)) {
        return __('API key is required', 'easyai-chat');
    }
    
    // Basic format validation
    switch ($provider) {
        case 'openai':
            if (!preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key)) {
                return __('Invalid OpenAI API key format. Should start with "sk-"', 'easyai-chat');
            }
            break;
            
        case 'anthropic':
            if (!preg_match('/^sk-ant-[a-zA-Z0-9]{32,}$/', $api_key)) {
                return __('Invalid Anthropic API key format. Should start with "sk-ant-"', 'easyai-chat');
            }
            break;
            
        case 'gemini':
            if (strlen($api_key) < 20) {
                return __('Gemini API key seems too short', 'easyai-chat');
            }
            break;
    }
    
    return true;
}