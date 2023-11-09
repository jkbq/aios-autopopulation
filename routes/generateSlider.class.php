<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class AiosSlider {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/slider', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_aios_slider'),
        ));
    }

    public function aios_populate_aios_slider($data) {

		$dateComplete = get_option('aios_auto_population_slider_date', $data['date']);

		$generatedSlideshow = get_option('aios_auto_population_slider');

		if (!$generatedSlideshow) {


			$response = Helpers::data('config.json');

			$data =  json_decode($response['body']);


			$slider_data = array(
				'post_type'     => 'aios-slider',
				'post_title'    => 'HP Slideshow (Auto Generated)',
				'post_status'   => 'publish',
				'post_author'   => 1,
			);

			$slider_id = wp_insert_post( $slider_data );
		
			update_option('aios_auto_population_slider', $slider_id );
			
			$slider_meta = array();
			
			$images = $data->slideshow[0]->images;
			$settings = $data->slideshow[0]->settings[0];


			$ip_banner_uploaded = false;
			foreach ( $images as $index => $image ) {

				$imagesPath = get_stylesheet_directory_uri() . '/' . $image->extension . '/images/';
				$src = media_sideload_image(  $imagesPath . $image->image, null, null, 'id' );

				$slider_meta[] = array(
					'type'    => 'image',
					'image'	  => $src,
					'tagline' => array(
						'title' 	  => $image->title,
						'description' => $image->description,
					),
				);

				// for innerpage image banner
				if ( !$ip_banner_uploaded ) {
					update_option( 'aios-metaboxes-default-banner-image', $src );
					$ip_banner_uploaded = true;
				}
			}
			update_post_meta( $slider_id, '_aios_slider_slides', $slider_meta );

			$slider_settings = array(
				'theme' 			=> array(
					'name'			=> 'default',
					'location'		=> 'core'
				),
				'width'				=> $settings->width,
				'height'			=> $settings->height,
				'min_height'		=> $settings->min_height,
				'min_height_unit'	=> $settings->min_height_unit,
				'transition_speed'	=> $settings->transition_speed,
				'interval'			=> $settings->interval,
				'autoplay'			=> $settings->autoplay,
				'arrows'			=> $settings->arrows,
				'dots'				=> $settings->dots,
				'random'			=> $settings->random,
			);
			update_post_meta( $slider_id, '_aios_slider_settings', $slider_settings );


			// Settings
			$aios_slider_options = get_option('aios_slider');
			foreach($settings->extensions as $key=>$value){

				if(!empty($value)){
					$aios_slider_options['extensions'][$key] = $value;
				}
			}

			
			$aios_slider_options['enqueue'] = $settings->random;
			update_option( 'aios_slider', $aios_slider_options );



			update_option('aios_auto_population_slider_date', $dateComplete );

			
			$response = array(
				'success' => true, 
				'message' => 'Slideshow Generated', 
				'date' => $dateComplete, 
			);


		}else{
			
			$response = array(
				'success' => false, 
				'message' => 'Slideshow Already Generated', 
				'date' => $dateComplete, 
			);

		}


		return rest_ensure_response($response);
    }
}
new AiosSlider();