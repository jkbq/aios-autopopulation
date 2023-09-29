<?php 

namespace AiosAutoPopulate\AiosPopulationPost;

class AiosPopulationPost {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/posts', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_default_settings'),
        ));
    }

    public function aios_populate_default_settings($data) {

        // Your logic to generate a page goes here
        $post_data = array(
            'post_title'    => 'Generated Page',
            'post_content'  => 'This is the content of the generated page.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            $response = array('success' => true, 'message' => 'Page generated successfully.', 'post_id' => $post_id);
        } else {
            $response = array('success' => false, 'message' => 'Error generating page.');
        }

        return rest_ensure_response($response);
    }
}

$AiosPopulationPost = new AiosPopulationPost();
