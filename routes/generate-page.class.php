<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class PagePopulate {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/page-populate', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_page'),
        ));
    }

    public function aios_populate_page($data) {

        $dateComplete = get_option('aios_auto_population_page_date', $data['date']);
        
        $pages_generated = get_option('aios_auto_population_page', false);
        
        $response_data = array();

        $response_data['date'] = $dateComplete;

        if (!$pages_generated) {

            $url =  get_stylesheet_directory_uri() .'/contents.json';

			$response = wp_remote_get($url, array(
				'timeout' => 45,
				'blocking' => true,
				'cookies' => array()
			));

            if (is_wp_error($response)) {
                error_log(print_r($response->get_error_message(), true));
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {
                $contents = json_decode($response['body']);

                foreach ($contents as $key=>$content) {

                    if($key === 'page'){
                        foreach ($content as $value) {
                            
                            $post_data = array(
                                'post_type'    => $value->post_type,
                                'post_title'   => $value->post_title,
                                'post_content' =>  $value->post_content,
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                            );
    
                            $insert_post = wp_insert_post($post_data);
                        
                            if ($insert_post) {


                                    
                                if($value->post_title === 'About'){
                                    $about_options = get_option('about_options');

                                    $about_options['page_id'] = $insert_post;
                                    update_option('about_options', $about_options);

                                }

                                if ($value->post_title === 'Contact') {

                                    $contact_options = get_option('contact_options');

                                    $contact_options['page_id'] = $insert_post;

                                    update_option('contact_options', $contact_options);
                                }
                        

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
                                $response_data['message'] = 'Page generated successfully';

                            }else {
                                $response_data['status'] = 'error';
                                $response_data['message'] = 'Error inserting post';
                            }

                        }
                    }
                }
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_page', true);
                update_option('aios_auto_population_page_date',  $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Page already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new PagePopulate();