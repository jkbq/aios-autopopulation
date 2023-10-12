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


        
        $activate_initial_setup_assets = get_option( 'activate_initial_setup_assets' );

        if($activate_initial_setup_assets != 'loaded'){

            /// Default Libraries
            $aios_enqueue_cdn = get_option( 'aios-enqueue-cdn' );
            $aios_enqueue_cdn['aos'] = '1';
            $aios_enqueue_cdn['slick'] = '1';
            $aios_enqueue_cdn['splitNav'] = '1';
            $aios_enqueue_cdn['videoPlyr'] = '1';
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
            $aios_client_info = get_option( 'aiis_ci' );
            $aios_client_info[ 'name' ] = $aios_client_info[ 'name' ] != '' ? $aios_client_info[ 'name' ] : 'Eric Davis';
            $aios_client_info[ 'email' ] = $aios_client_info[ 'email' ] != '' ? $aios_client_info[ 'email' ] : 'agent@agentimage.com';
            $aios_client_info[ 'phone' ] = $aios_client_info[ 'phone' ] != '' ? $aios_client_info[ 'phone' ] : '123.456.7890';
            $aios_client_info[ 'address' ] = $aios_client_info[ 'address' ] != '' ? $aios_client_info[ 'address' ] : '1700 East Walnut Avenue, Suite 400, El Segundo, CA 90245';
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
            update_option( 'blogname', $aios_client_info[ 'name' ] );
            update_option( 'blogdescription', 'Responsive Real Estate WordPress Theme from Agent Image' );

            // sets permalink custom structure
            update_option( 'permalink_structure', '/%category%/%postname%/' );

            // for yoast seo
            $wpseo_titles = get_option( 'wpseo_titles' );
            $wpseo_titles[ 'breadcrumbs-enable' ] = true;
            $wpseo_titles[ 'post_types-post-maintax' ] = 'category';
            update_option( 'wpseo_titles', $wpseo_titles );

            update_option('activate_initial_setup_assets', 'loaded');
           
        }

        $response = array('success' => true, 'message' => 'Page generated successfully.', 'post_id' =>  $activate_initial_setup_assets);



        return rest_ensure_response($response);
    }
}
new Settings();