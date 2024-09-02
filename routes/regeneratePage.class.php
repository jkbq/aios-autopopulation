<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class RegenerateContents {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/regeneratecontents', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_repopulate_page'),
        ));
    }

    public function aios_repopulate_page() {

        $url =  get_stylesheet_directory_uri() .'/config.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));

        $config =  json_decode($response['body']);

        $beforeTheme = get_option('aios_autopopulation_theme');

        $active_theme = get_option('template');

        $active_child_theme = get_option('stylesheet');

        $active_theme = $active_theme === 'aios-starter-theme' ?  $active_child_theme : $active_theme;

        // Default Libraries
        $libraries = $config->config[0]->libraries;

        $client_info = $config->config[0]->site_info;
        $post_title_option = get_option('aios-metaboxes-custom-title-post-types');
        $taxonomy_title_option = get_option('aios-metaboxes-custom-title-taxonomies');

        $product_type = $config->product_type;
        $old_theme_slug = 'theme_mods_' . $beforeTheme; // Replace 'old_theme_slug' with the slug of the old theme

        $theme_mods = get_option($old_theme_slug);

        if( $product_type === 'AgentImagex'){
            if ($theme_mods !== false) {
                foreach ($theme_mods as $mod_name => $mod_value) {
                    if($mod_name !== 'nav_menu_locations'){
                        set_theme_mod($mod_name, $mod_value);
                    }
                }
            }

            // Check if the 'Main Nav' menu exists
            $menu_name = 'Main Nav';
            $menu_exists = wp_get_nav_menu_object($menu_name);

            $menu_id = $menu_exists->term_id;

            // Retrieve current theme mods for the theme
            $theme_mods = get_option("theme_mods_$active_child_theme");

            // Ensure $theme_mods['nav_menu_locations'] is an array
            if (!isset($theme_mods['nav_menu_locations']) || !is_array($theme_mods['nav_menu_locations'])) {
                $theme_mods['nav_menu_locations'] = array();
            }

            // Set the 'primary-menu' location to the 'Main Nav' menu ID
            $location = 'primary-menu';
            $theme_mods['nav_menu_locations'][$location] = $menu_id;

            // Update the theme mods option
            update_option("theme_mods_$active_child_theme", $theme_mods);
            
        }
        if ($theme_mods !== false) {
            $aix_client_phone_arrs                  =   $theme_mods['aios-agent-profile-phone-number'];
            $aix_client_phone_arrs                  =   json_decode($aix_client_phone_arrs);
            $aix_client_email                       =   $theme_mods['aios-agent-profile-email'];
            $welcome_photo                          = $theme_mods['aios-welcome-photo'];
            $aios_client_info = get_option('aiis_ci');
            $aios_client_info['name'] = $client_info->name;
            $aios_client_info['email'] = $aix_client_email;
            $aios_client_info['phone'] = $aix_client_phone_arrs->phone;
            $aios_client_info['country-code-phone'] = $aix_client_phone_arrs->country;
            $aios_client_info['photo'] = wp_get_attachment_image_url($welcome_photo, 'full');
            update_option('aiis_ci', $aios_client_info);
        }

        
        if (isset($client_info->banner_title_inside)){
    
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

        }else{
            $taxonomy_title_option['title']['asiowpfiller'] = '';
            $taxonomy_title_option['title']['category'] = '';
            $taxonomy_title_option['title']['community-group'] = '';
            $taxonomy_title_option['title']['property-features'] = '';
            $taxonomy_title_option['title']['property-types'] = '';
            $taxonomy_title_option['title']['property-statuses'] = '';
            $taxonomy_title_option['title']['property-states'] = '';

            $post_title_option['title']['post'] = 'post';
            $post_title_option['title']['page'] = 'page';
            $post_title_option['title']['aios-neighborhood'] = '';
            $post_title_option['title']['aios-agents'] = '';
            $post_title_option['title']['aios-communities'] = '';
            $post_title_option['title']['aios-concierge'] = '';
            $post_title_option['title']['aios-rm-buyers'] = '';
            $post_title_option['title']['aios-rm-financing'] = '';
            $post_title_option['title']['aios-rm-sellers'] = '';
            $post_title_option['title']['aios-testimonials'] = '';

            $inside_banner = get_option('aios-metaboxes-banner-title-layout');
            $inside_banner[1] = '';
            update_option('aios-metaboxes-banner-title-layout', $inside_banner);
            update_option('aios-metaboxes-custom-title-post-types', $post_title_option);
            update_option('aios-metaboxes-custom-title-taxonomies', $taxonomy_title_option);
        }

        $wpseo_titles = get_option('wpseo_titles');
        $wpseo_titles['breadcrumbs-enable'] = true;
        $wpseo_titles['post_types-post-maintax'] = 'category';
        update_option('wpseo_titles', $wpseo_titles);

        $settings = $config->slideshow[0]->settings[0];

        $aios_slider_options = get_option('aios_slider');
        foreach ($settings->extensions as $key => $value) {

            if (!empty($value)) {
                $aios_slider_options['extensions'][$key] = $value;
            }
        }
        $aios_slider_options['enqueue'] = $settings->enqueue;
        update_option('aios_slider', $aios_slider_options);

        $communitiesConfig = $config->config[0]->plugins->aios_communities;
        // aios-communities
        update_option( 'communities-themes', ''.$communitiesConfig->theme.'-core' );


        $aios_enqueue_cdn = get_option( 'aios-enqueue-cdn' );

        foreach (  $libraries as $key=>$value){
            $aios_enqueue_cdn[$key] = $value;
        }

        update_option( 'aios-enqueue-cdn', $aios_enqueue_cdn );


        $productType = $config->config[0]->product_type;

        // about and contact regenerate 
        $about =  $config->about_contact[0]->about;
        $about_options = get_option('about_options');



        $aios_client_info = get_option('aiis_ci');
        
        $aios_client_info['photo'] = wp_get_attachment_image_url($about_options['agent_team_photo'], 'full');
        update_option('aiis_ci', $aios_client_info);

        $about_options['theme'] = $productType . '-' . $about->theme;
        
        update_option('about-theme', $productType . '-' . $about->theme);

        autoPopulateCustomPages(
            'about',
            $about->theme,
            true
        );


        update_option('about_options', $about_options);


        // Contact 
        $contact =  $config->about_contact[0]->contact;
        $contact_options = get_option('contact_options');

        $contact_options['theme'] = $productType . '-' . $contact->theme;

        update_option('contact-theme', $productType . '-' . $contact->theme);

        autoPopulateCustomPages(
            'contact',
            $contact->theme,
            true
        );
        update_option('contact_options', $contact_options);


        update_option('aios_autopopulation_theme', $active_theme);

        $response = array(
            'success' => true,
            'message' => 'Regenerate Successful'
        );

        
        return rest_ensure_response($response);
    }
}
new RegenerateContents();