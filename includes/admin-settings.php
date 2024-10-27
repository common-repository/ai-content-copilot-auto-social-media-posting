<?php
defined('ABSPATH') or die('Direct script access disallowed.');

function aiccfb_add_admin_menu() {
    add_menu_page('AI Content to Facebook Settings', 'AI to Facebook', 'manage_options', 'aiccfb_facebook', 'aiccfb_create_page', 'dashicons-admin-generic');
}

add_action('admin_menu', 'aiccfb_add_admin_menu');

function aiccfb_create_page() {
    $options = get_option('aiccfb_facebook_settings');
    $app_id = $options['facebook_app_id'] ?? ''; // Replace 'default_app_id' with a default value or an empty string

    // Handle manual post submission
    if (isset($_POST['fb_post_submit'])) {
        // Verify nonce
        if (!isset($_POST['aiccfb_manual_post_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aiccfb_manual_post_nonce'])), 'aiccfb_manual_post_nonce_action')) {
            wp_die(__('Nonce verification failed!', 'aiccfb-auto-social-media-posting'));
        }

        $post_content = sanitize_textarea_field($_POST['fb_post_content']);
        $image_url = esc_url_raw($_POST['fb_post_image']); // Ensure this is a valid URL

        // Call a function to post the content to Facebook
        $result = aiccfb_post_to_facebook($post_content, $image_url);
        echo '<div id="message" class="updated fade"><p>' . esc_html($result) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Content to Facebook</h1>
        <p>Click the button below to log in with Facebook:</p>

        <!-- Facebook Login Button -->
        <div id="fb-root"></div>
        <fb:login-button scope="public_profile,email" onlogin="aiccfb_check_login_state();"></fb:login-button>
        <div id="status"></div>

        <!-- Settings form for OpenAI API key, Facebook Page ID, and Posting Instructions -->
        <form method="post" action="options.php">
            <?php
            settings_fields('aiccfb_facebook_plugin_settings');
            do_settings_sections('aiccfb_facebook');
            submit_button();
            ?>
        </form>

        <!-- Manual Post to Facebook Section -->
        <h2>Manual Post to Facebook</h2>
        <form method="post" action="">
            <textarea name="fb_post_content" placeholder="Post content" rows="4" cols="50"></textarea><br>
            <input type="text" name="fb_post_image" placeholder="Image URL (optional)"><br>
            <?php wp_nonce_field('aiccfb_manual_post_nonce_action', 'aiccfb_manual_post_nonce'); ?>
            <input type="submit" name="fb_post_submit" value="Post to Facebook">
        </form>

        <!-- AI Generated Post to Facebook Section -->
        <h2>AI Generated Post to Facebook</h2>
        <textarea id="ai_post_description" placeholder="Describe what you want in your post" rows="2" cols="50"></textarea><br>
        <button id="ai_generate_content" class="button button-primary">Generate Content with AI</button>

        <!-- Container for AI-generated content -->
        <div id="ai_generated_content_container" style="display:none;">
            <textarea id="ai_generated_content" placeholder="AI-generated content will appear here" rows="4" cols="50"></textarea><br>
            <button id="ai_post_to_facebook" class="button button-secondary">Post AI Content to Facebook</button>
        </div>
        <?php wp_nonce_field('aiccfb_facebook_nonce_action', 'aiccfb_facebook_nonce'); ?>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#ai_generate_content').click(function(e) {
                e.preventDefault();
                var postDescription = $('#ai_post_description').val();
                var data = {
                    'action': 'generate_ai_content',
                    'prompt': postDescription,
                    'aiccfb_facebook_nonce': $('#aiccfb_facebook_nonce').val()
                };

                $.post(ajaxurl, data, function(response) {
                    $('#ai_generated_content').val(response);
                    $('#ai_generated_content_container').show();
                });
            });

            $('#ai_post_to_facebook').click(function(e) {
                e.preventDefault();
                var content = $('#ai_generated_content').val();
                var data = {
                    'action': 'post_ai_content_to_facebook',
                    'content': content,
                    'aiccfb_facebook_nonce': $('#aiccfb_facebook_nonce').val()
                };

                $.post(ajaxurl, data, function(response) {
                    alert("Response from Facebook: " + response);
                });
            });
        });
    </script>

    <script>
        var facebookAppId = '<?php echo esc_js($app_id); ?>';
        if (typeof FB === 'undefined') {
            window.fbAsyncInit = function() {
                FB.init({
                    appId: facebookAppId,
                    cookie: true,
                    xfbml: true,
                    version: 'v19.0'
                });

                FB.AppEvents.logPageView();
                FB.getLoginStatus(function(response) {
                    aiccfb_status_change_callback(response);
                });
            };

            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "https://connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        } else {
            console.log('Facebook SDK already initialized.');
            FB.getLoginStatus(function(response) {
                aiccfb_status_change_callback(response);
            });
        }

        function aiccfb_check_login_state() {
            FB.getLoginStatus(function(response) {
                aiccfb_status_change_callback(response);
            });
        }

        function aiccfb_status_change_callback(response) {
            console.log('Facebook login status changed:', response);
            var statusElement = document.getElementById('status');
            if (statusElement) {
                if (response.status === 'connected') {
                    statusElement.innerHTML = 'Thanks for logging in, ' + response.authResponse.userID + '!';
                } else if (response.status === 'not_authorized') {
                    statusElement.innerHTML = 'Please authorize this app.';
                } else {
                    statusElement.innerHTML = 'Please log into Facebook.';
                }
            } else {
                console.error('Status element not found.');
            }
        }
    </script>
    <?php
}

function aiccfb_page_init() {
    register_setting('aiccfb_facebook_plugin_settings', 'aiccfb_facebook_settings');

    add_settings_section('aiccfb_plugin_index', 'Settings', 'aiccfb_settings_details', 'aiccfb_facebook');

    add_settings_field('aiccfb_openai_api_key', 'OpenAI API Key', 'aiccfb_openai_key_callback', 'aiccfb_facebook', 'aiccfb_plugin_index');
    add_settings_field('aiccfb_facebook_page_id', 'Facebook Page ID', 'aiccfb_facebook_page_id_callback', 'aiccfb_facebook', 'aiccfb_plugin_index');
    add_settings_field('aiccfb_facebook_access_token', 'Facebook Access Token', 'aiccfb_facebook_access_token_callback', 'aiccfb_facebook', 'aiccfb_plugin_index');
    add_settings_field('aiccfb_facebook_app_id', 'Facebook App ID', 'aiccfb_facebook_app_id_callback', 'aiccfb_facebook', 'aiccfb_plugin_index');
}

add_action('admin_init', 'aiccfb_page_init');

function aiccfb_settings_details() {
    echo esc_html('Configure your API keys and posting instructions here.');
}

function aiccfb_openai_key_callback() {
    $options = get_option('aiccfb_facebook_settings');
    echo '<input type="text" name="aiccfb_facebook_settings[openai_api_key]" value="' . esc_attr($options['openai_api_key'] ?? '') . '" />';
}

function aiccfb_facebook_page_id_callback() {
    $options = get_option('aiccfb_facebook_settings');
    echo '<input type="text" name="aiccfb_facebook_settings[facebook_page_id]" value="' . esc_attr($options['facebook_page_id'] ?? '') . '" />';
}

function aiccfb_facebook_access_token_callback() {
    $options = get_option('aiccfb_facebook_settings');
    echo '<input type="text" name="aiccfb_facebook_settings[facebook_access_token]" value="' . esc_attr($options['facebook_access_token'] ?? '') . '" />';
}

function aiccfb_facebook_app_id_callback() {
    $options = get_option('aiccfb_facebook_settings');
    echo '<input type="text" name="aiccfb_facebook_settings[facebook_app_id]" value="' . esc_attr($options['facebook_app_id'] ?? '') . '" />';
}

function aiccfb_post_to_facebook($content, $image_url = '') {
    $options = get_option('aiccfb_facebook_settings');
    if (empty($options['facebook_access_token']) || empty($options['facebook_page_id'])) {
        return "Facebook access token or page ID not set or empty.";
    }

    $page_access_token = aiccfb_get_page_access_token($options['facebook_access_token'], $options['facebook_page_id']);
    if (!$page_access_token) {
        return "Failed to get page access token.";
    }

    if (!empty($image_url)) {
        // Post with an image
        $url = "https://graph.facebook.com/{$options['facebook_page_id']}/photos";
        $data = [
            'url' => $image_url,
            'caption' => $content,
            'access_token' => $page_access_token,
        ];
    } else {
        // Text-only post
        $url = "https://graph.facebook.com/{$options['facebook_page_id']}/feed";
        $data = [
            'message' => $content,
            'access_token' => $page_access_token,
        ];
    }

    $response = wp_remote_post($url, [
        'body' => $data,
        'method' => 'POST',
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return "Error posting to Facebook: $error_message";
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['error'])) {
        return "Error posting to Facebook: " . $body['error']['message'];
    }
    return "Successfully posted to Facebook. Post ID: " . $body['id'];
}

function aiccfb_get_page_access_token($user_access_token, $page_id) {
    $url = "https://graph.facebook.com/{$page_id}?fields=access_token&access_token={$user_access_token}";
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['access_token'])) {
        return $body['access_token'];
    }

    return false;
}

add_action('wp_ajax_generate_ai_content', 'aiccfb_handle_generate_ai_content');

function aiccfb_handle_generate_ai_content() {
    // Verify nonce
    check_ajax_referer('aiccfb_facebook_nonce_action', 'aiccfb_facebook_nonce');
    $prompt = isset($_POST['prompt']) ? sanitize_text_field($_POST['prompt']) : '';
    $content = aiccfb_generate_content_with_gpt4($prompt);
    echo wp_kses_post($content);
    wp_die(); // Terminate the AJAX request correctly
}

// New AJAX action for posting content
add_action('wp_ajax_post_ai_content_to_facebook', 'aiccfb_handle_post_ai_content_to_facebook');

function aiccfb_handle_post_ai_content_to_facebook() {
    // Verify nonce
    check_ajax_referer('aiccfb_facebook_nonce_action', 'aiccfb_facebook_nonce');
    if (!current_user_can('publish_posts')) {
        wp_die('Insufficient permissions'); // Check user capabilities
    }

    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    // Assuming you might have an image URL, adjust as needed
    $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';

    $result = aiccfb_post_to_facebook($content, $image_url);
    echo esc_html($result);
    wp_die(); // Terminate the AJAX request correctly
}
?>
