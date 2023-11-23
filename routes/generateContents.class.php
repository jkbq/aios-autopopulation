<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class Contents {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/contents', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_contents'),
        ));
    }

    public function aios_populate_contents($data) {

        $dateComplete = get_option('aios_auto_population_default_contents_date', $data['date']);
        
        $pages_generated = get_option('aios_auto_population_default_contents', false);
        
        $response_data = array();

        $response_data['date'] = $dateComplete;

        if (!$pages_generated) {
        
            $response = Helpers::data('contents.json');

            $cid = wp_insert_term(
                'Blog', 'category',
                array( 'slug' => 'blog'
            ) );

            if (is_wp_error($response)) {
                error_log(print_r($response->get_error_message(), true));
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {
               

                $contents = json_decode($response['body']);

                foreach ($contents as $content) {
                    foreach ($content as $value) {

                        $contentData = '';
                        if ($value->post_type === 'aios-testimonials') {
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
                            'page_template' => $value->page_template
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

                            if ($value->post_type === 'aios-agents') {
                                
                                $meta_input = json_decode(json_encode($value->meta_input), true);
                                $meta_input = $meta_input[0];
                                $meta_input['agentimage_id'] = $image_data;

                                update_post_meta($insert_post, '_agent_details', $meta_input);

                                update_post_meta( $insert_post, 'first_name', $value->meta_input->first_name );
                                update_post_meta( $insert_post, 'last_name', $value->meta_input->last_name );
                                update_post_meta( $insert_post, 'full_name', $value->meta_input->first_name .' '. $value->meta_input->last_name );
                                update_post_meta( $insert_post, 'position', $value->meta_input->position );
                                update_post_meta( $insert_post, 'license', $value->meta_input->license );
                                update_post_meta( $insert_post, 'email', $value->meta_input->email_address );


                            }

                            // Debugging: Check if post is inserted successfully
                            error_log('Post inserted with ID: ' . $insert_post);
                            $response_data['status'] = 'success';
                            $response_data['message'] = 'Contents generated successfully';
                        } else {
                            // Debugging: Check if there is an error during post insertion
                            error_log('Error inserting post: ' . $insert_post->get_error_message());
                            $response_data['status'] = 'error';
                            $response_data['message'] = 'Error inserting post';
                        }
                    }
                }
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_default_contents', true);
                update_option('aios_auto_population_default_contents_date',  $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Contents already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new Contents();