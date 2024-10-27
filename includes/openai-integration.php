<?php
defined('ABSPATH') or die('Direct script access disallowed.');

function aiccfb_generate_content_with_gpt4($prompt) {
    $options = get_option('ai_content_facebook_settings');
    $api_key = $options['openai_api_key']; // Make sure this is correctly set in your plugin's settings

    $response = wp_remote_post('https://api.openai.com/v1/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => wp_json_encode([
            'model' => 'gpt-3.5-turbo-instruct', // Specify the model you want to use
            'prompt' => $prompt,
            'max_tokens' => 100,
            'temperature' => 0.7
        ]),
        'method' => 'POST',
        'data_format' => 'body'
    ]);

    // Check for errors in the WP HTTP API response
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        $error_message = is_wp_error($response) ? $response->get_error_message() : 'HTTP Error ' . wp_remote_retrieve_response_code($response);
        // error_log('Error contacting OpenAI: ' . $error_message);
        return 'Error: ' . $error_message; // This will be returned to the AJAX call and shown to the user
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for errors returned by the OpenAI API
    if (isset($data['error'])) {
        // error_log('OpenAI Error: ' . $data['error']['message']);
        return "Error: " . $data['error']['message']; // This will be returned to the AJAX call and shown to the user
    }

    // Return the generated content, or 'No content generated.' if nothing is returned
    return isset($data['choices'][0]['text']) ? $data['choices'][0]['text'] : 'No content generated.';
}

function aiccfb_openai_post_to_facebook($message) {
    $options = get_option('ai_content_facebook_settings');
    // error_log(print_r($options, true));

    $access_token = $options['facebook_access_token']; // Ensure you have a setting field for this
    $page_id = get_option('facebook_page_id', ''); // Get the page ID from settings

    $url = "https://graph.facebook.com/{$page_id}/feed?access_token={$access_token}";

    $data = array(
        'message' => $message,
    );

    $response = wp_remote_post($url, array(
        'body' => $data,
        'method' => 'POST',
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

function aiccfb_setup_schedule() {
    if (!wp_next_scheduled('ai_content_facebook_generate_and_post')) {
        wp_schedule_event(time(), 'hourly', 'ai_content_facebook_generate_and_post');
    }
}

add_action('wp', 'aiccfb_setup_schedule');

function aiccfb_do_this_hourly() {
    $prompt = "Write a short and engaging post about the latest trends in technology.";
    $content = aiccfb_generate_content_with_gpt4($prompt);
    aiccfb_openai_post_to_facebook($content);
}

add_action('ai_content_facebook_generate_and_post', 'aiccfb_do_this_hourly');
