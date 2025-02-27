<?php
/**
 * Admin functionality for EasyAI-Chat
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue admin scripts and styles
 */
function easyai_chat_admin_enqueue_scripts($hook) {
    if ('settings_page_easyai-chat-settings' !== $hook) {
        return;
    }

    wp_enqueue_style('easyai-chat-admin', EASYAI_CHAT_PLUGIN_URL . 'admin/css/admin-style.css', array(), EASYAI_CHAT_VERSION);
    wp_enqueue_script('easyai-chat-admin', EASYAI_CHAT_PLUGIN_URL . 'admin/js/admin-script.js', array('jquery', 'wp-color-picker'), EASYAI_CHAT_VERSION, true);
    wp_enqueue_style('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'easyai_chat_admin_enqueue_scripts');

/**
 * Add admin menu
 */
function easyai_chat_add_admin_menu() {
    add_options_page(
        'EasyAI-Chat Settings',
        'EasyAI-Chat',
        'manage_options',
        'easyai-chat-settings',
        'easyai_chat_settings_page'
    );
}
add_action('admin_menu', 'easyai_chat_add_admin_menu');

/**
 * Register settings
 */
function easyai_chat_register_settings() {
    register_setting('easyai_chat_settings', 'easyai_chat_options');
}
add_action('admin_init', 'easyai_chat_register_settings');

/**
 * Render the settings page
 */
function easyai_chat_settings_page() {
    $options = get_option('easyai_chat_options');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="easyai-chat-admin-tabs">
            <div class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab nav-tab-active">API Settings</a>
                <a href="#chat-settings" class="nav-tab">Chat Settings</a>
                <a href="#appearance" class="nav-tab">Appearance</a>
                <a href="#usage" class="nav-tab">Usage</a>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('easyai_chat_settings'); ?>
                
                <!-- API Settings Tab -->
                <div id="api-settings" class="tab-content active">
                    <h2>API Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">LLM Provider</th>
                            <td>
                                <select name="easyai_chat_options[llm_provider]" id="llm_provider">
                                    <option value="openai" <?php selected($options['llm_provider'], 'openai'); ?>>OpenAI (ChatGPT)</option>
                                    <option value="anthropic" <?php selected($options['llm_provider'], 'anthropic'); ?>>Anthropic (Claude)</option>
                                    <option value="gemini" <?php selected($options['llm_provider'], 'gemini'); ?>>Google (Gemini)</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">API Key</th>
                            <td>
                                <input type="password" 
                                       name="easyai_chat_options[api_key]" 
                                       value="<?php echo esc_attr($options['api_key']); ?>" 
                                       class="regular-text"
                                />
                                <p class="description">
                                    <span class="provider-info openai">Get your OpenAI API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a></span>
                                    <span class="provider-info anthropic" style="display:none;">Get your Anthropic API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></span>
                                    <span class="provider-info gemini" style="display:none;">Get your Gemini API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></span>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">System Prompt</th>
                            <td>
                                <textarea name="easyai_chat_options[system_prompt]" 
                                          rows="5" 
                                          class="large-text"><?php echo esc_textarea($options['system_prompt']); ?></textarea>
                                <p class="description">This prompt guides how the AI responds to questions.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Temperature</th>
                            <td>
                                <input type="range" 
                                       name="easyai_chat_options[temperature]"
                                       min="0" 
                                       max="1" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($options['temperature']); ?>" 
                                />
                                <span class="temperature-value"><?php echo esc_html($options['temperature']); ?></span>
                                <p class="description">Controls randomness: 0 is very focused, 1 is more creative.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Chat Settings Tab -->
                <div id="chat-settings" class="tab-content">
                    <h2>Chat Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Max Response Tokens</th>
                            <td>
                                <input type="number" 
                                       name="easyai_chat_options[max_tokens]" 
                                       value="<?php echo intval($options['max_tokens']); ?>" 
                                       class="small-text"
                                       min="50"
                                       max="2000"
                                />
                                <p class="description">Maximum length of the AI's response (in tokens).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Allowed Questions Per User</th>
                            <td>
                                <input type="number" 
                                       name="easyai_chat_options[allowed_questions]" 
                                       value="<?php echo intval($options['allowed_questions']); ?>" 
                                       class="small-text"
                                       min="1"
                                       max="10"
                                />
                                <p class="description">Number of questions a user can ask before seeing the app promotion.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">App Store URL</th>
                            <td>
                                <input type="url" 
                                       name="easyai_chat_options[app_store_url]" 
                                       value="<?php echo esc_url($options['app_store_url'] ?? ''); ?>" 
                                       class="regular-text"
                                />
                                <p class="description">URL where users can download your app</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Promotion Message</th>
                            <td>
                                <input type="text" 
                                       name="easyai_chat_options[promotion_message]" 
                                       value="<?php echo esc_attr($options['promotion_message'] ?? 'Want more insights? Download our app!'); ?>" 
                                       class="regular-text"
                                />
                                <p class="description">Message shown after allowed questions are used</p>
                            </td>
                        </tr>
                        <!-- Example Questions Section -->
                        <tr>
                            <th scope="row">Example Questions</th>
                            <td>
                                <div class="example-questions-container">
                                    <div class="example-question-item">
                                        <input type="text" 
                                            name="easyai_chat_options[example_questions][]" 
                                            value="<?php echo esc_attr($options['example_questions'][0] ?? 'What can you help me with today?'); ?>" 
                                            class="regular-text"
                                        />
                                    </div>
                                    <div class="example-question-item">
                                        <input type="text" 
                                            name="easyai_chat_options[example_questions][]" 
                                            value="<?php echo esc_attr($options['example_questions'][1] ?? 'How does this work?'); ?>" 
                                            class="regular-text"
                                        />
                                    </div>
                                    <button type="button" class="button add-example-question">Add Another Question</button>
                                </div>
                            </td>
                        </tr>

                        <!-- Limit Message Section -->
                        <tr>
                            <th scope="row">Limit Reached Message</th>
                            <td>
                                <textarea name="easyai_chat_options[limit_message]" 
                                        rows="3" 
                                        class="large-text"><?php echo esc_textarea($options['limit_message'] ?? 'You\'ve reached the maximum number of questions. Click the button below to continue.'); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Limit Button Text</th>
                            <td>
                                <input type="text" 
                                    name="easyai_chat_options[limit_button_text]" 
                                    value="<?php echo esc_attr($options['limit_button_text'] ?? 'Continue'); ?>" 
                                    class="regular-text"
                                />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Limit Button URL</th>
                            <td>
                                <input type="url" 
                                    name="easyai_chat_options[limit_button_url]" 
                                    value="<?php echo esc_url($options['limit_button_url'] ?? ''); ?>" 
                                    class="regular-text"
                                />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Appearance Tab -->
                <div id="appearance" class="tab-content">
                    <h2>Appearance Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Display Type</th>
                            <td>
                                <select name="easyai_chat_options[display_type]">
                                    <option value="inline" <?php selected($options['display_type'], 'inline'); ?>>Inline</option>
                                    <option value="popup" <?php selected($options['display_type'], 'popup'); ?>>Popup</option>
                                </select>
                                <p class="description">Choose how you want the chat widget to appear on your site</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Button Position (for popup)</th>
                            <td>
                                <select name="easyai_chat_options[position]">
                                    <option value="bottom-right" <?php selected($options['position'], 'bottom-right'); ?>>Bottom Right</option>
                                    <option value="bottom-left" <?php selected($options['position'], 'bottom-left'); ?>>Bottom Left</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Theme Color</th>
                            <td>
                                <input type="text" 
                                       name="easyai_chat_options[theme_color]" 
                                       value="<?php echo esc_attr($options['theme_color']); ?>"
                                       class="color-picker"
                                />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Button Icon</th>
                            <td>
                                <select name="easyai_chat_options[button_icon]">
                                    <option value="chat" <?php selected($options['button_icon'], 'chat'); ?>>Chat</option>
                                    <option value="message" <?php selected($options['button_icon'], 'message'); ?>>Message</option>
                                    <option value="help" <?php selected($options['button_icon'], 'help'); ?>>Help</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Chat Title</th>
                            <td>
                                <input type="text" 
                                       name="easyai_chat_options[chat_title]" 
                                       value="<?php echo esc_attr($options['chat_title'] ?? 'EasyAI Chat'); ?>" 
                                       class="regular-text"
                                />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Chat Subtitle</th>
                            <td>
                                <input type="text" 
                                       name="easyai_chat_options[chat_subtitle]" 
                                       value="<?php echo esc_attr($options['chat_subtitle'] ?? 'Ask me anything!'); ?>" 
                                       class="regular-text"
                                />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Usage Tab -->
                <div id="usage" class="tab-content">
                    <h2>Usage Instructions</h2>
                    
                    <div class="card">
                        <h3>Using the Shortcode</h3>
                        <p>Add the chat widget to any page or post using this shortcode:</p>
                        <code>[easyai_chat]</code>
                    </div>
                    
                    <div class="card">
                        <h3>PHP Integration</h3>
                        <p>You can also add the chat widget to your theme files using PHP:</p>
                        <pre>
&lt;?php echo do_shortcode('[easyai_chat]'); ?&gt;
                        </pre>
                    </div>
                    
                    <div class="card">
                        <h3>API Usage Tracking</h3>
                        <p>Be aware that each API call to your chosen LLM provider may incur costs. Check your provider's pricing page for details:</p>
                        <ul>
                            <li><a href="https://openai.com/pricing" target="_blank">OpenAI Pricing</a></li>
                            <li><a href="https://www.anthropic.com/pricing" target="_blank">Anthropic Pricing</a></li>
                            <li><a href="https://ai.google.dev/pricing" target="_blank">Google AI Pricing</a></li>
                        </ul>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <?php
}