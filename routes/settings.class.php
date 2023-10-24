<?php 

namespace AiosAutoPopulate\Routes;

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
        
        $dateComplete = get_option('activate_initial_setup_assets_date_complete', $data['date']);

        $currentDateTime = date('m/d/Y, g:i:s A');
        update_option('activate_initial_setup_assets_date_complete',  $currentDateTime);

        $activate_initial_setup_assets = get_option( 'activate_initial_setup_assets' );

        $jsonData = get_stylesheet_directory_uri() . '/config.json';

        $response = wp_remote_get($jsonData, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));

        $data =  json_decode($response['body']);

        if($activate_initial_setup_assets != 'loaded'){

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


            // Client Info
            $client_info = $data->config[0]->site_info;
            $aios_client_info = get_option( 'aiis_ci' );
            $aios_client_info[ 'name' ] = $aios_client_info[ 'name' ] != '' ? $aios_client_info[ 'name' ] : $client_info->name;
            $aios_client_info[ 'email' ] = $aios_client_info[ 'email' ] != '' ? $aios_client_info[ 'email' ] : $client_info->email;
            $aios_client_info[ 'phone' ] = $aios_client_info[ 'phone' ] != '' ? $aios_client_info[ 'phone' ] : $client_info->phone;

            $aios_client_info[ 'address' ] = $aios_client_info[ 'address' ] != '' ? $aios_client_info[ 'address' ] : $client_info->address;
            $aios_client_info[ 'address_street' ] = $aios_client_info[ 'address_street' ] != '' ? $aios_client_info[ 'address_unit' ] : $client_info->address_unit;
            $aios_client_info[ 'address_city' ] = $aios_client_info[ 'address_city' ] != '' ? $aios_client_info[ 'address_city' ] : $client_info->address_city;
            $aios_client_info[ 'address_state' ] = $aios_client_info[ 'address_state' ] != '' ? $aios_client_info[ 'address_state' ] : $client_info->address_state;
            $aios_client_info[ 'address_zip' ] = $aios_client_info[ 'address_zip' ] != '' ? $aios_client_info[ 'address_zip' ] : $client_info->address_zip;

            

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

            // aios-communities
			update_option( 'communities-themes', 'galaxy-core' );

            update_option('activate_initial_setup_assets', 'loaded');

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