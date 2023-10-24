<?php 

namespace AiosAutoPopulate\Routes;

class Communities {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/communities', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_contents'),
        ));
    }

    public function aios_populate_contents($data) {

        $currentDateTime = date('m/d/Y, g:i:s A');
        $dateComplete = get_option('communities_date_complete', $data['date']);

        // Check if pages have already been generated
        $communities = get_option('communities', false);
        $response_data = array();

        $response_data['date'] = $dateComplete;

        if (!$communities) {

            
            $jsonData = get_stylesheet_directory_uri() . '/config.json';

         
            $response = wp_remote_get($jsonData, array(
                'timeout' => 45,
                'blocking' => true,
                'cookies' => array()
            ));

            $data =  json_decode($response['body']);

            if (is_wp_error($response)) {
                error_log(print_r($response->get_error_message(), true));
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {
                
                
                $communities  = $data->aios_communities;

          
                foreach ($communities as $value) {

                    $post_data = array(
                        'post_type'    => 'aios-communities',
                        'post_title'   => $value->post_title,
                        'post_content' => $value->post_content,
                        'post_status'  => 'publish',
                        'post_author'  => 1,
                    );

                    // Check post type and set category accordingly
                    if ($value->post_type == 'post' && isset($value->post_category_id)) {
                        $post_data['post_category'] = array(get_cat_ID( 'Blog' ));
                    }

                    $insert_post = wp_insert_post($post_data);
                    
                    if ($insert_post) {
                        // Post inserted successfully

                        $extension = !empty($value->extension) ? ''.$value->extension.'/' : '';
                        $image_url = get_stylesheet_directory_uri() . '/' . $extension . 'images/' . $value->featured_image;
                        $image_data = media_sideload_image($image_url, $insert_post, '', 'id');
                        // Set featured image using media_sideload_image
                        if (isset($value->featured_image)) {
                            

                            // Check if there is an error in sideloading the image
                            if (is_wp_error($image_data)) {
                                error_log('Error sideloading featured image: ' . $image_data->get_error_message());
                            } else {
                                // Get the attachment ID from the image data
                                $image_id = $image_data;

                                // Set the featured image
                                set_post_thumbnail($insert_post, $image_id);

                                // Debugging: Log the success
                                error_log('Featured image set for post ID: ' . $insert_post);
                            }


                            // Set thumbnail
                            if (!is_wp_error($image_id)) {
                                set_post_thumbnail($insert_post, $image_id);
                            }
                        }
                        // Debugging: Check if post is inserted successfully
                        error_log('Post inserted with ID: ' . $insert_post);
                        $response_data['status'] = 'success';
                        $response_data['message'] = 'Communities generated successfully';
                    } else {
                        // Debugging: Check if there is an error during post insertion
                        error_log('Error inserting post: ' . $insert_post->get_error_message());
                        $response_data['status'] = 'error';
                        $response_data['message'] = 'Error inserting post';
                    }
                }
                

                // Set the option to indicate that pages have been generated
                // update_option('communities', true);
                update_option('communities_date_complete',  $currentDateTime);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Communities already generated';

        }
        

        return rest_ensure_response($response_data);
    }
}
new Communities();