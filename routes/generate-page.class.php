<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class PagePopulate {
    public function __construct() 
    {
        add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
    }

    public function register_endpoints() 
    {
        register_rest_route('aios-populate/v1', '/page-populate', [
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_page'),
        ]);
    }

    public function aios_populate_page($data) 
    {
        $dateComplete = get_option('aios_auto_population_page_date', $data['date']);
        $pages_generated = get_option('aios_auto_population_page', false);
        $response_data = [];
        $response_data['date'] = $dateComplete;

        if (!$pages_generated) {

            $active_theme = get_option('template');


            $sPath = get_template_directory_uri();
    
        
            if ( $active_theme  === 'aios-starter-theme') {
                $sPath = get_stylesheet_directory_uri();
            }
            
            $url = $sPath .'/contents.json';
            
			$response = wp_remote_get($url, [
				'timeout' => 45,
				'blocking' => true,
				'cookies' => [],
			]);

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

                                if (isset($value->page_template) && !empty($value->page_template)) {
                                    update_post_meta( $insert_post, '_wp_page_template', $value->page_template );
                                }

                                $extension = !empty($value->extension) ? '' . $value->extension . '/' : '';
                                $image_url = $sPath . '/' . $extension . 'images/' . $value->featured_image;
                                $image_data = media_sideload_image($image_url, $insert_post, '', 'id');
    
                                if (isset($value->featured_image)) {
                                    if (is_wp_error($image_data)) {
                                        error_log('Error sideloading featured image: ' . $image_data->get_error_message());
                                    } else {
                                        $image_id = $image_data;
                                        set_post_thumbnail($insert_post, $image_id);
                                        error_log('Featured image set for post ID: ' . $insert_post);
                                    }
    
                                    if (!is_wp_error($image_id)) {
                                        set_post_thumbnail($insert_post, $image_id);
                                    }
                                }

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

                update_option('aios_auto_population_page', true);
                update_option('aios_auto_population_page_date',  $dateComplete);

                // After page population let's delete the sample page
                $sample_page = get_posts([
                    'name'        => 'sample-page',
                    'post_type'   => 'page',
                    'post_status' => 'publish',
                    'numberposts' => 1
                ]);
                
                if (!empty($sample_page)) {
                    $page_id = $sample_page[0]->ID; 
                    wp_delete_post($page_id, true);
                }


                $defaultsData = AIOS_AUTOPOPULATE_JSON .'/default.json';
            
                $response_defaults = wp_remote_get($defaultsData, [
                    'timeout' => 45,
                    'blocking' => true,
                    'cookies' => [],
                ]);
                if (is_wp_error($response_defaults)) {
                    error_log(print_r($response_defaults->get_error_message(), true));
                    $response_data['status'] = 'error';
                    $response_data['message'] = 'Error fetching JSON data';
                } else {

                    $contents = json_decode($response_defaults['body']);

                    foreach ($contents as $key=>$content) {


                        foreach ($content as $value) {
                            // Insert Privacy Policy page
                            $privacy_policy_page = array(
                                'post_type'    => 'page',
                                'post_title'   => $value->post_title,
                                'post_content' =>  $value->post_content,
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                            );

                            $privacy_policy_id = wp_insert_post($privacy_policy_page);

                           
                        }

                    }
                }
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Page already generated';
        }

        return rest_ensure_response($response_data);
    }
}
new PagePopulate();