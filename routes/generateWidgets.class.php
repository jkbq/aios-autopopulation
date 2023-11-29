<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class Widgets {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/widgets', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_contents'),
        ));
    }

    public function widgetGenerator( $sidebar, $name, $args = array() ) {


        
			if ( ! $sidebars = get_option( 'sidebars_widgets') )

				$sidebars = array();

			// Create the sidebar if it doesn't exist.
			if ( ! isset( $sidebars[ $sidebar ] ) ){
				$sidebars[ $sidebar ] = array();
			}

			// Check for existing saved widgets.
			$widget_opts = get_option( "widget_$name" );
			$still_empty = false;

			// Make sure that the widget array is really empty
			if ( is_array( $widget_opts ) ) {
				$still_empty = count( $widget_opts ) === 1 && array_key_exists('_multiwidget',$widget_opts);
			}

			if ( $widget_opts && !$still_empty ) {
				// Get next insert id.
				ksort( $widget_opts );
				end( $widget_opts );
				$insert_id = key( $widget_opts );

			} else {
				// None existing, start fresh.
				$widget_opts = array( '_multiwidget' => 1 );
				$insert_id = 0;
			}

			// Add our settings to the stack.
			$widget_opts[ ++$insert_id ] = $args;

			// Add our widget!
			$sidebars[ $sidebar ][] = "$name-$insert_id";

			update_option( 'sidebars_widgets', $sidebars );
			update_option( "widget_$name", $widget_opts );
    }




    public function aios_populate_contents($data) {
        
        
        /// Repopulation
        if ($data['repopulate']){
            delete_option('aios_auto_population_widgets');
            delete_option('aios_auto_population_widgets_date');
        }

        $dateComplete = get_option('aios_auto_population_widgets_date', $data['date']);
        
        $wigets_generated = get_option('aios_auto_population_widgets', false);

        $widget_install = new widgets();

        if (!$wigets_generated) {

            $registered_sidebars = wp_get_sidebars_widgets();
            $sidebars_widgets = get_option('sidebars_widgets');

            foreach ($registered_sidebars as $sidebar_id => $widgets) {
                // Remove all widgets from the current sidebar
                $sidebars_widgets[$sidebar_id] = array();
                
                // Update the sidebars_widgets option to reflect the changes
                update_option('sidebars_widgets', $sidebars_widgets);

                // Optionally, you can update other options to indicate that the sidebar is deregistered
                update_option($sidebar_id . '-deregistered', 'yes');
                update_option($sidebar_id . '-deregistered-timestamp', time()); // You can store a timestamp if needed
            }


            $response = Helpers::data('config.json');

            $data =  json_decode($response['body']);

            $widgets  = $data->widgets;

        
            foreach ( $widgets as $widget_info ) {
        
                // Replace specific content in the $args array
                if (isset( $widget_info->args->pbcw_category)) {
                    // for blog
                    $widget_info->args->pbcw_category = get_cat_ID( 'Blog' );
                }

                $widget_args = [];

                foreach ( $widget_info->args as $arg_key => $arg_value ) {                
                    $widget_args[ $arg_key ]  = $arg_value;
                }
                
                $widget_install->widgetGenerator(
                    $widget_info->id,
                    $widget_info->type,
                    $widget_args
                );
            }

            $communitiesConfig = $data->config[0]->plugins->aios_communities;
            $agentsConfig = $data->config[0]->plugins->aios_agents;
            $client_info = $data->config[0]->site_info;
            
            /// Plugins Settings
            // Testimonials
            $testimonials_options = get_option('aios_testimonials_settings');
            $testimonial_page = get_page_by_title('Testimonials');
            $testimonials_options['main_page'] = $testimonial_page->ID ;
            $testimonials_options['primary_color'] = $client_info->primary_color;

            // Communities
            $aiosCommunities = get_option('aios_communities_settings');
            $get_communities_page = get_page_by_title('Communities');
            $aiosCommunities['main_page'] = $get_communities_page->ID;
            $aiosCommunities['show_overlay'] = $communitiesConfig->show_overlay_overlay;
            $aiosCommunities['overlay_color'] = $communitiesConfig->overlay_color;
            $aiosCommunities['opacity_percentage'] = $communitiesConfig->opacity_percentage;
            $aiosCommunities['primary_color'] = $client_info->primary_color;

            // Listings
            $listings = get_option('listings_settings');
            $get_properties_page = get_page_by_title('Properties');
            $get_properties_featured = get_page_by_title('Featured Listings');
            $listings['main_page'] = $get_properties_page->ID;
            $listings['featured_property_page'] = $get_properties_featured->ID;

            // Agents
            $agents = get_option('agents_settings');
            $get_agents_page = get_page_by_title('Meet The Team');
            $agents['main_page'] = $get_agents_page->ID;
            $agents['primary_color'] = $client_info->primary_color;
            $agents['hover_color'] = $client_info->primary_color;

            /// Roadmaps 
            $aiosRoadmaps = get_option('aios_roadmaps_settings');
            $aiosRoadmaps['primary_color'] = "#000000";

            update_option( 'aios_testimonials_settings', $testimonials_options );
            update_option('aios_communities_settings', $aiosCommunities );
            update_option('listings_settings', $listings );
            update_option('listings_results_page_primary_color', $client_info->primary_color);
            update_option('listings_results_page_secondary_color', '#ffffff');
            update_option('agents_settings', $agents );
            update_option('aios_roadmaps_settings', $aiosRoadmaps );

            
            // aios-communities
			update_option( 'communities-themes', ''.$communitiesConfig->theme.'-core' );

            // aios-testimonials
			update_option( 'agent-main-page', ''.$agentsConfig->theme.'-core' );
			update_option( 'agent-details-page', ''.$agentsConfig->theme.'-core' );


            // Set the option to indicate that pages have been generated
            update_option('aios_auto_population_widgets', true);
            update_option('aios_auto_population_widgets_date',  $dateComplete);

            $response_data['status'] = true;
            $response_data['message'] = 'Widgets generated successfully';
            $response_data['date'] = $dateComplete;

        }else{
            $response_data['status'] = false;
            $response_data['message'] = 'Widgets already generated';
            $response_data['date'] = $dateComplete;
        }

        return rest_ensure_response($response_data);
    }
}
new widgets();