<?php
/**
 * Plugin Name: EasyAI-Chat
 * Description: Add AI chat functionality to your WordPress site with support for multiple LLM providers
 * Version: 1.0.0
 * Author: Naomi Nour
 * Text Domain: easyai-chat
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EASYAI_CHAT_VERSION', '1.0.0');
define('EASYAI_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EASYAI_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once EASYAI_CHAT_PLUGIN_DIR . 'includes/helpers.php';
require_once EASYAI_CHAT_PLUGIN_DIR . 'includes/api-handlers.php';
require_once EASYAI_CHAT_PLUGIN_DIR . 'admin/admin.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'easyai_chat_activate');
register_deactivation_hook(__FILE__, 'easyai_chat_deactivate');

/**
 * Plugin activation function
 */
function easyai_chat_activate() {
    // Set default options
    $default_options = array(
        'llm_provider' => 'openai',
        'api_key' => '',
        'system_prompt' => 'You are a helpful AI assistant.',
        'max_tokens' => 150,
        'temperature' => 0.7,
        'allowed_questions' => 1,
        'display_type' => 'inline',
        'position' => 'bottom-right',
        'theme_color' => '#6750A4',
        'button_icon' => 'chat'
    );
    
    add_option('easyai_chat_options', $default_options);
    
    // Create necessary directories
    $dirs = array(
        EASYAI_CHAT_PLUGIN_DIR . 'public/css',
        EASYAI_CHAT_PLUGIN_DIR . 'public/js',
        EASYAI_CHAT_PLUGIN_DIR . 'admin/css',
        EASYAI_CHAT_PLUGIN_DIR . 'admin/js'
    );
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
}

/**
 * Plugin deactivation function
 */
function easyai_chat_deactivate() {
    // Cleanup if needed
}

/**
 * Enqueue public scripts and styles
 */
function easyai_chat_enqueue_scripts() {
    wp_enqueue_style('easyai-chat-public', EASYAI_CHAT_PLUGIN_URL . 'public/css/public-style.css', array(), EASYAI_CHAT_VERSION);
    wp_enqueue_script('easyai-chat-public', EASYAI_CHAT_PLUGIN_URL . 'public/js/public-script.js', array('jquery'), EASYAI_CHAT_VERSION, true);
    
    // Pass settings to JavaScript
    $options = get_option('easyai_chat_options');
    wp_localize_script('easyai-chat-public', 'easyAIChat', array(
        'apiUrl' => esc_url_raw(rest_url('easyai-chat/v1/chat')),
        'nonce' => wp_create_nonce('wp_rest'),
        'displayType' => esc_attr($options['display_type']),
        'position' => esc_attr($options['position']),
        'themeColor' => esc_attr($options['theme_color']),
        'buttonIcon' => esc_attr($options['button_icon']),
        'allowedQuestions' => intval($options['allowed_questions']),
        'exampleQuestions' => $options['example_questions'] ?? array('What can you help me with today?', 'How does this work?'),
        'limitMessage' => $options['limit_message'] ?? 'You\'ve reached the maximum number of questions. Click the button below to continue.',
        'limitButtonText' => $options['limit_button_text'] ?? 'Continue',
        'limitButtonUrl' => $options['limit_button_url'] ?? '',
    ));
}
add_action('wp_enqueue_scripts', 'easyai_chat_enqueue_scripts');

/**
 * Register REST API routes
 */
function easyai_chat_register_routes() {
    register_rest_route('easyai-chat/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'easyai_chat_process_request',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'easyai_chat_register_routes');

/**
 * Add shortcode
 */
function easyai_chat_shortcode() {
    $template_path = EASYAI_CHAT_PLUGIN_DIR . 'public/templates/chat-widget.php';
    
    // If template doesn't exist, return simple div
    if (!file_exists($template_path)) {
        return '<div id="easyai-chat-root"></div>';
    }
    
    ob_start();
    include $template_path;
    return ob_get_clean();
}
add_shortcode('easyai_chat', 'easyai_chat_shortcode');