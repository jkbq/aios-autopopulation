<?php 

namespace AiosAutoPopulate\Routes;

class Form {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/form', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_form'),
        ));
    }

    public function aios_populate_form($data) {


        foreach ($sample_posts as $value) {
            $post_data = array(
                'post_type'    => $value->post_type,
                'post_title'   => $value->post_title,
                'post_content' => $value->post_content,
                'post_status'  => 'publish',
                'post_author'  => 1,
            );

            // Check post type and set category accordingly
            if ($value->post_type == 'post' && isset($value->post_category_id)) {
                $post_data['post_category'] = array($value->post_category_id); // Use the specified category ID for 'post'
            }

            $insert_post = wp_insert_post($post_data);

            // Set featured image using media_sideload_image
            if ($insert_post && isset($value->image_name)) {

                $base_url = get_stylesheet_directory_uri();
                $image_url =  $base_url . '/' . $value->image_name; // Construct the image URL
                $image_id = media_sideload_image($image_url, $insert_post, null, 'id');

                if (!is_wp_error($image_id)) {
                    set_post_thumbnail($insert_post, $image_id);
                }
            }
        }
        return rest_ensure_response($response);
    }
}
