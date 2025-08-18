<?php


add_action('rest_api_init', function () {
    register_rest_route('aios-populate/data', '/config', [
        'methods' => 'GET',
        'callback' => 'get_json_config',
    ]);
    register_rest_route('aios-populate/data', '/content', [
        'methods' => 'GET',
        'callback' => 'get_json_contents',
    ]);
});


function get_json_config($request)
{
    // Path to your JSON file
    $json_file_path = get_stylesheet_directory() . '/config.json';

    // Check if the file exists
    if (!file_exists($json_file_path)) {
        return new WP_Error('file_not_found', 'JSON file not found', ['status' => 404]);
    }

    // Read the contents of the JSON file
    $json_data = file_get_contents($json_file_path);

    // Check if the JSON data could be read
    if ($json_data === false) {
        return new WP_Error('file_read_error', 'Error reading JSON file', ['status' => 500]);
    }

    // Decode the JSON data
    $decoded_data = json_decode($json_data, true);

    // Check if decoding was successful
    if ($decoded_data === null) {
        return new WP_Error('json_decode_error', 'Error decoding JSON data', ['status' => 500]);
    }

    // Return the decoded JSON data
    return $decoded_data;
}


function get_json_contents($request)
{
    // Path to your JSON file
    $json_file_path = get_stylesheet_directory() . '/contents.json';

    // Check if the file exists
    if (!file_exists($json_file_path)) {
        return new WP_Error('file_not_found', 'JSON file not found', ['status' => 404]);
    }

    // Read the contents of the JSON file
    $json_data = file_get_contents($json_file_path);

    // Check if the JSON data could be read
    if ($json_data === false) {
        return new WP_Error('file_read_error', 'Error reading JSON file', ['status' => 500]);
    }

    // Decode the JSON data
    $decoded_data = json_decode($json_data, true);

    // Check if decoding was successful
    if ($decoded_data === null) {
        return new WP_Error('json_decode_error', 'Error decoding JSON data', ['status' => 500]);
    }

    // Return the decoded JSON data
    return $decoded_data;
}
