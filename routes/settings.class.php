<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class Settings {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/settings', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_default_settings'),
        ));
    }

    public function aios_populate_default_settings($data) {
        
        $dateComplete = get_option('aios_auto_population_initial_setup_assets_date', $data['date']);
        update_option('aios_auto_population_initial_setup_assets_date',  $dateComplete);
        $activate_initial_setup_assets = get_option( 'aios_auto_population_initial_setup_assets', false );
        

        $url =  get_stylesheet_directory_uri() .'/config.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));

        $data =  json_decode($response['body']);

        if($activate_initial_setup_assets != true){
            // Default Libraries
            $libraries = $data->config[0]->libraries;

            $aios_enqueue_cdn = get_option( 'aios-enqueue-cdn' );

            foreach (  $libraries as $key=>$value){
                $aios_enqueue_cdn[$key] = $value;
            }

            update_option( 'aios-enqueue-cdn', $aios_enqueue_cdn );

            update_option( 'aios-metaboxes-banner-not-found', '404 Pages' );
            $aios_banner_post_types = get_option( 'aios-metaboxes-banner-post-types');
            $aios_banner_post_types['banner']['post']  = 'post';
            $aios_banner_post_types['banner']['page']  = 'page';
            $aios_banner_post_types['banner']['aios-testimonials']  = 'aios-testimonials';
            $aios_banner_post_types['banner']['aios-communities']  = 'aios-communities';
            update_option( 'aios-metaboxes-banner-post-types', $aios_banner_post_types );

            $aios_banner_taxonomies = get_option( 'aios-metaboxes-banner-taxonomies');
            $aios_banner_taxonomies['banner']['category'] = 'category';
            update_option( 'aios-metaboxes-banner-taxonomies', $aios_banner_taxonomies  );

            update_option( 'aios_custom_login_screen', 'agentpro' );
            update_option( 'aios_auto_p_metabox', '1' );

            // Initial Setup - Quick Search
            update_option( 'aios-quick-search', ['enabled' => 1] );

            // Client Info
            $client_info = $data->config[0]->site_info;
            $aios_client_info = get_option( 'aiis_ci' );
            $aios_client_info[ 'name' ] = $aios_client_info[ 'name' ] != '' ? $aios_client_info[ 'name' ] : $client_info->name;
            $aios_client_info[ 'email' ] = $aios_client_info[ 'email' ] != '' ? $aios_client_info[ 'email' ] : $client_info->email;
            $aios_client_info[ 'phone' ] = $aios_client_info[ 'phone' ] != '' ? $aios_client_info[ 'phone' ] : $client_info->phone;
            $aios_client_info[ 'company_name' ] = $aios_client_info[ 'company_name' ] != '' ? $aios_client_info[ 'company_name' ] : $client_info->company_name;

            $aios_client_info[ 'address' ] = $aios_client_info[ 'address' ] != '' ? $aios_client_info[ 'address' ] : $client_info->address;
            $aios_client_info[ 'address_street' ] = $aios_client_info[ 'address_street' ] != '' ? $aios_client_info[ 'address_street' ] : $client_info->address_street;
            $aios_client_info[ 'address_unit' ] = $aios_client_info[ 'address_unit' ] != '' ? $aios_client_info[ 'address_unit' ] : $client_info->address_unit;
            $aios_client_info[ 'address_city' ] = $aios_client_info[ 'address_city' ] != '' ? $aios_client_info[ 'address_city' ] : $client_info->address_city;
            $aios_client_info[ 'address_state' ] = $aios_client_info[ 'address_state' ] != '' ? $aios_client_info[ 'address_state' ] : $client_info->address_state;
            $aios_client_info[ 'address_zip' ] = $aios_client_info[ 'address_zip' ] != '' ? $aios_client_info[ 'address_zip' ] : $client_info->address_zip;





            if (isset($client_info->agent_photo)) {
            /// agent photo 
                $imagesPath = get_stylesheet_directory_uri() . '/' . $client_info->agent_photo->extension . '/images/';
                $src = media_sideload_image($imagesPath .$client_info->agent_photo->image, null, null, 'src');

                $aios_client_info['photo'] = $src;
            }


            if (isset($client_info->banner_title_inside)){

                $post_title_option = get_option('aios-metaboxes-custom-title-post-types');
                $taxonomy_title_option = get_option('aios-metaboxes-custom-title-taxonomies');

                $taxonomy_title_option['title']['asiowpfiller'] = 'asiowpfiller';
                $taxonomy_title_option['title']['category'] = 'category';
                $taxonomy_title_option['title']['community-group'] = 'community-group';
                $taxonomy_title_option['title']['property-features'] = 'property-features';
                $taxonomy_title_option['title']['property-types'] = 'property-types';
                $taxonomy_title_option['title']['property-statuses'] = 'property-statuses';
                $taxonomy_title_option['title']['property-states'] = 'property-states';


                $post_title_option['title']['post'] = 'post';
                $post_title_option['title']['page'] = 'page';
                $post_title_option['title']['aios-neighborhood'] = 'aios-neighborhood';
                $post_title_option['title']['aios-agents'] = 'aios-agents';
                $post_title_option['title']['aios-communities'] = 'aios-communities';
                $post_title_option['title']['aios-concierge'] = 'aios-concierge';
                $post_title_option['title']['aios-rm-buyers'] = 'aios-rm-buyers';
                $post_title_option['title']['aios-rm-financing'] = 'aios-rm-financing';
                $post_title_option['title']['aios-rm-sellers'] = 'aios-rm-sellers';
                $post_title_option['title']['aios-testimonials'] = 'aios-testimonials';


                $inside_banner = get_option('aios-metaboxes-banner-title-layout');
                $inside_banner[1] = 1;
                update_option('aios-metaboxes-banner-title-layout', $inside_banner);

                
                update_option('aios-metaboxes-custom-title-post-types', $post_title_option);
                update_option('aios-metaboxes-custom-title-taxonomies', $taxonomy_title_option);
            }

            $default_social_media_links = [
                "facebook" => 'https://www.facebook.com/AgentImage',
                "twitter" => 'https://www.twitter.com/agentimage',
                "youtube" => 'https://www.youtube.com/agentimage',
                "linkedin" => 'https://www.linkedin.com/company/agent-image',
                "pinterest" => 'https://www.pinterest.com/agentimage/',
                "instagram" => 'https://www.instagram.com/agentimage/'
            ];

            foreach ($default_social_media_links as $key => $value) {
                $aios_client_info[ $key ] = $aios_client_info[ $key ] ? $aios_client_info[ $key ] : $value;
            }
            update_option( 'aiis_ci', $aios_client_info );

                
            // Modules
            $aios_initial_setup_modules = get_option( 'aios_initial_setup_modules' );
            $aios_initial_setup_modules[ 'classic-editor' ] = 'yes';
            $aios_initial_setup_modules[ 'classic-editor-widget' ] = 'yes';
            update_option( 'aios_initial_setup_modules', $aios_initial_setup_modules );

            // updates blog name and description
            update_option('blogname', '');
            update_option('blogdescription', '');
            update_option( 'blogname', $aios_client_info[ 'name' ] );
            update_option( 'blogdescription', 'Responsive Real Estate WordPress Theme from Agent Image' );

            // sets permalink custom structure
            update_option( 'permalink_structure', '/%category%/%postname%/' );

            // for yoast seo
            $wpseo_titles = get_option( 'wpseo_titles' );
            $wpseo_titles[ 'breadcrumbs-enable' ] = true;
            $wpseo_titles[ 'post_types-post-maintax' ] = 'category';
            update_option( 'wpseo_titles', $wpseo_titles );

            update_option('aios_auto_population_initial_setup_assets', true);


            $response = array(
                'success' => true, 
                'message' => 'Settings Successfully Generated', 
                'date' => $dateComplete
            );

        }else{
    
            $response = array(
                'success' => false, 
                'message' => 'Settings Already Generated', 
                'date' => $dateComplete
            );
        }

        return rest_ensure_response($response);
    }
}
new Settings();