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
        
        $active_theme = get_option('template');


        $sPath = get_template_directory_uri();

    
        if ( $active_theme  === 'aios-starter-theme') {
            $sPath = get_stylesheet_directory_uri();
        }

        
        
        $url =  $sPath .'/config.json';

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

            $aios_banner_404['404 Pages'] = '404 Pages';
            update_option('aios-metaboxes-banner-not-found', $aios_banner_404);
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
            $aios_client_info[ 'license' ] = $aios_client_info[ 'license' ] != '' ? $aios_client_info[ 'license' ] : $client_info->license;
            
            $aios_client_info[ 'address' ] = $aios_client_info[ 'address' ] != '' ? $aios_client_info[ 'address' ] : wp_kses_post($client_info->address);
            $aios_client_info[ 'address_street' ] = $aios_client_info[ 'address_street' ] != '' ? $aios_client_info[ 'address_street' ] : $client_info->address_street;
            $aios_client_info[ 'address_unit' ] = $aios_client_info[ 'address_unit' ] != '' ? $aios_client_info[ 'address_unit' ] : $client_info->address_unit;
            $aios_client_info[ 'address_city' ] = $aios_client_info[ 'address_city' ] != '' ? $aios_client_info[ 'address_city' ] : $client_info->address_city;
            $aios_client_info[ 'address_state' ] = $aios_client_info[ 'address_state' ] != '' ? $aios_client_info[ 'address_state' ] : $client_info->address_state;
            $aios_client_info[ 'address_zip' ] = $aios_client_info[ 'address_zip' ] != '' ? $aios_client_info[ 'address_zip' ] : $client_info->address_zip;



            $back_to_top_config = $data->config[0]->back_to_top;

            $aios_back_to_top = get_option('aios-back-top');

          
            if(isset($back_to_top_config)){
                $back_to_top_icon = $sPath . '/' . $back_to_top_config->extension . '/images/' . $back_to_top_config->image_icon;
                $backToTopIcon = media_sideload_image( $back_to_top_icon, '0', '', 'id');
        
                $backToTopIconUrl = wp_get_attachment_url($backToTopIcon, 'full');
                $aios_back_to_top['image-icon'] = $backToTopIconUrl;

                foreach($back_to_top_config as $key => $value){
                    
                    if($key != 'extension') {
                        $aios_back_to_top[$key] = $value;
                    }
                }
            } else {
                $aios_back_to_top['enabled'] = "1";
                $aios_back_to_top['pages'] = "all";
                $aios_back_to_top['right'] = "15";
                $aios_back_to_top['bottom'] = "15";
                $aios_back_to_top['transition'] = "5";
                $aios_back_to_top['offset'] = "100";
                $aios_back_to_top['height'] = "60";
                $aios_back_to_top['svg-width'] = "32";
                $aios_back_to_top['border-style'] = "none";
                $aios_back_to_top['border-color'] = "#000000";
                $aios_back_to_top['hover-border-color'] = "#4f4f4f";
                $aios_back_to_top['background-color'] = "#000000";
                $aios_back_to_top['hover-background-color'] = "#4f4f4f";
                $aios_back_to_top['shadow-color'] = "rgba(0,0,0,0)";
                $aios_back_to_top['font-size'] = "16";
                $aios_back_to_top['text-color'] = "#ffffff";
                $aios_back_to_top['hover-text-color'] = "#ffffff";
                $aios_back_to_top['icon'] = "default";
                $aios_back_to_top['text-gap'] = "10";
            }
            update_option('aios-back-top', $aios_back_to_top);


            $productType = $data->product_type;
            $default_social_media_links = [
                "facebook" => 'https://www.facebook.com/AgentImage',
                "twitter" => 'https://www.twitter.com/agentimage',
                "youtube" => 'https://www.youtube.com/agentimage',
                "linkedin" => 'https://www.linkedin.com/company/agent-image',
                "pinterest" => 'https://www.pinterest.com/agentimage/',
                "instagram" => 'https://www.instagram.com/agentimage/'
            ];
            foreach ($default_social_media_links as $key => $value) {
                $aios_client_info[$key] = $aios_client_info[$key] ? $aios_client_info[$key] : $value;

                if(isset($productType) && $productType === 'AgentImagex'){
                    set_theme_mod('aios-social-media-'.$key.'', $value);
                }

            }

            // Add Logo and Brokerage Logo
            if(isset($client_info->logo)){
                $logoUrl = $sPath . '/' . $client_info->logo->extension . '/images/' . $client_info->logo->image;
                $clientLogo = media_sideload_image($logoUrl, '0', '', 'id');
                
                $clientlogoUrl = wp_get_attachment_image_url($clientLogo, 'full');
            
                $aios_client_info[ 'logo' ] =  $clientlogoUrl;
            }
            
            if(isset($client_info->brokerage_logo)){
                $brokerageLogoUrl = $sPath . '/' . $client_info->brokerage_logo->extension . '/images/' . $client_info->brokerage_logo->image;

                $brokerageLogo = media_sideload_image($brokerageLogoUrl, '0', '', 'id');

                $brokerageLogoUrlUp = wp_get_attachment_image_url($brokerageLogo, 'full');
                $aios_client_info[ 'ip-logo' ] = $brokerageLogoUrlUp;
            }

            update_option('aiis_ci', $aios_client_info);

     
            if (isset($client_info->no_content_breadcurmbs) || $client_info->no_content_breadcurmbs === true){

                update_option('aios-metaboxes-breadcrumb', '1');
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

            if($client_info->featured_image_banner){
                $featured_banner_lists = get_option('aios-metaboxes-featured-banner-image');
                foreach($client_info->featured_image_banner as $value){
                    $featured_banner_lists[$value] = $value;
                }
                update_option('aios-metaboxes-featured-banner-image', $featured_banner_lists);

            }
                
            // Modules
            $aios_initial_setup_modules = get_option( 'aios_initial_setup_modules' );
            $aios_initial_setup_modules[ 'classic-editor' ] = 'yes';
            $aios_initial_setup_modules[ 'classic-editor-widget' ] = 'yes';
            $aios_initial_setup_modules[ 'contact-form-7-floating-tooltip-fix' ] = 'yes';
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

            $wpseo_titles['breadcrumbs-sep'] = '>';
            
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