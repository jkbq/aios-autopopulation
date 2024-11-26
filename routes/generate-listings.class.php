<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class Listings {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/listings', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_listings'),
        ));
    }

    public function aios_populate_listings($data) {

        $dateComplete = get_option('aios_auto_population_listings_date', $data['date']);
        
        $pages_generated = get_option('aios_auto_population_listings', false);
        
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

                    if($key === 'aios-listings'){
                        foreach ($content as $value) {

                            $contentData = '';
                            if ($value->post_type === 'aios-listings') {
                                $aios_client_info = get_option( 'aiis_ci' );
                                $contentData = str_replace("ai_client_name", $aios_client_info[ 'name' ] , $value->post_content); 
                            }else{
                                $contentData = $value->post_content;
                            }
                            
                            $post_data = array(
                                'post_type'    => $value->post_type,
                                'post_title'   => $value->post_title,
                                'post_content' => $contentData,
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                            );
    
                            $insert_post = wp_insert_post($post_data);
                        
                            if ($insert_post) {

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
    
                                if ($value->post_type === 'aios-listings') {
                                    // Additional code specific to 'aios-listings' post type
    
                                    if (property_exists($value, 'meta_input')) {
    
                                        
                                        $tax_status = get_term_by('slug', 'for-sale', 'property-statuses');
                                        $tax_type = get_term_by('slug', 'residential', 'property-types');
    
    
                                        $meta_input = json_decode(json_encode($value->meta_input), true);
                                        $meta_input = $meta_input[0];
                                        $meta_input['featured_image_id'] = $image_data;
                                        $meta_input['listing-gallery'][] = $image_data;
    
                                        foreach ($meta_input as $meta_key => $meta_value) {
                                            update_post_meta($insert_post, $meta_key, $meta_value);
                                        }
    
                                        update_post_meta($insert_post, '_listing_details', $meta_input);
                                        wp_set_post_terms($insert_post, [$tax_status->term_id], 'property-statuses');
                                        wp_set_post_terms($insert_post, [$tax_type->term_id], 'property-types');
                                    }
                                    // Continue with other actions specific to 'aios-listings' post type
                                }

                                // Debugging: Check if post is inserted successfully
                                error_log('Post inserted with ID: ' . $insert_post);
                                $response_data['status'] = 'success';
                                $response_data['message'] = 'Listings generated successfully';


                            }else {
                                $response_data['status'] = 'error';
                                $response_data['message'] = 'Error inserting post';
                            }

                        }
                    }
                }
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_listings', true);
                update_option('aios_auto_population_listings_date',  $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Listings already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new Listings();