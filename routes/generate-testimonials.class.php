<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class Testimonials {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/testimonials', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_testimonials'),
        ));
    }

    public function aios_populate_testimonials($data) {

        $dateComplete = get_option('aios_auto_population_testimonials_date', $data['date']);
        
        $pages_generated = get_option('aios_auto_population_testimonials', false);
        
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

                    if($key === 'aios-testimonials'){
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
                            );
    
                            wp_insert_post($post_data);

                        }

                        $response_data['status'] = 'success';
                        $response_data['message'] = 'Post generated successfully';

                    }
                }
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_testimonials', true);
                update_option('aios_auto_population_testimonials_date',  $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Testimonials already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new Testimonials();