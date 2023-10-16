<?php 

namespace AiosAutoPopulate\Routes;

class AiosSlider {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/slider', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_default_settings'),
        ));
    }

    public function aios_populate_default_settings($data) {

            $imagesPath = get_stylesheet_directory_uri() . '/images';
			$slider_data = array(
				'post_type'     => 'aios-slider',
				'post_title'    => 'HP Slideshow (Auto Generated)',
				'post_status'   => 'publish',
				'post_author'   => 1,
			);
			$slider_id = wp_insert_post( $slider_data );
			
			$slider_meta = array();
			$images = array(

				array(
					"image"=> $imagesPath .'/slide1.jpg',
					"title" => "",
					"description"=> ''
				),
				array(
					"image"=> $imagesPath .'/slide2.jpg',
					"title" => "",
					"description"=> ''
				),
				array(
					"image"=> $imagesPath .'/slide3.jpg',
					"title" => "",
					"description"=> ''
				),
				array(
					"image"=> $imagesPath .'/slide4.jpg',
					"title" => "",
					"description"=> ''
				),
				array(
					"image"=> $imagesPath .'/slide5.jpg',
					"title" => "",
					"description"=> ''
				)
			);
            
			$ip_banner_uploaded = false;
			foreach ( $images as $index => $image ) {
				$src = media_sideload_image( $image[ 'image' ], null, null, 'src' );

				$slider_meta[] = array(
					'type'    => 'image',
					'image'	  => attachment_url_to_postid( $src ),
					'tagline' => array(
						'title' 	  => '',
						'description' => '',
					),
				);

				// for innerpage image banner
				if ( !$ip_banner_uploaded ) {
					update_option( 'aios-metaboxes-default-banner-image', attachment_url_to_postid( $src ) );
					$ip_banner_uploaded = true;
				}
			}
			update_post_meta( $slider_id, '_aios_slider_slides', $slider_meta );

			$slider_settings = array(
				'theme' 			=> array(
					'name'			=> 'default',
					'location'		=> 'core'
				),
				'width'				=> '1600',
				'height'			=> '800',
				'min_height'		=> '800',
				'min_height_unit'	=> 'px',
				'transition_speed'	=> '1000',
				'interval'			=> '4000',
				'autoplay'			=> 'enable',
				'arrows'			=> 'hide',
				'dots'				=> 'hide',
				'random'			=> 'no',
			);
			update_post_meta( $slider_id, '_aios_slider_settings', $slider_settings );


        $response = array(
            'success' => false, 
            'message' => 'Roadmaps Already Generated', 
        );

    
        return rest_ensure_response($response);
    }
}