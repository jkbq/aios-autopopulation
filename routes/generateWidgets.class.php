<?php

namespace AIOS\AUTOPOPULATE\Routes;

class Widgets
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/widgets', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_contents'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    /**
     * Add a widget to the collected sidebar/widget arrays without writing to DB.
     * Call update_option() once after all widgets are processed.
     */
    private function widgetGeneratorBatch( array &$sidebars, array &$all_widget_opts, string $sidebar, string $name, array $args = [] ): void
    {
        if ( ! isset( $sidebars[$sidebar] ) ) {
            $sidebars[$sidebar] = [];
        }

        if ( ! isset( $all_widget_opts[$name] ) ) {
            $widget_opts = get_option("widget_$name", []);
            if ( ! $widget_opts || ( count($widget_opts) === 1 && isset($widget_opts['_multiwidget']) ) ) {
                $widget_opts = ['_multiwidget' => 1];
            }
            $all_widget_opts[$name] = $widget_opts;
        }

        $keys      = array_map('intval', array_keys($all_widget_opts[$name]));
        $insert_id = max($keys) + 1;

        $all_widget_opts[$name][$insert_id] = $args;
        $sidebars[$sidebar][]               = $name . '-' . $insert_id;
    }

    /** @deprecated Use widgetGeneratorBatch() for new code. Kept for backward compatibility. */
    public function widgetGenerator($sidebar, $name, $args = [])
    {
        $sidebars         = get_option('sidebars_widgets', []);
        $all_widget_opts  = [];
        $this->widgetGeneratorBatch($sidebars, $all_widget_opts, $sidebar, $name, $args);
        update_option('sidebars_widgets', $sidebars);
        foreach ($all_widget_opts as $wname => $wopts) {
            update_option("widget_$wname", $wopts);
        }
    }




    public function aios_populate_contents($data)
    {


        /// Repopulation
        if ($data['repopulate']) {
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
            delete_option('aios_auto_population_widgets');
            delete_option('aios_auto_population_widgets_date');
        }

        $dateComplete = get_option('aios_auto_population_widgets_date', $data['date']);

        $wigets_generated = get_option('aios_auto_population_widgets', false);

        $widget_install = new widgets();

        if (!$wigets_generated) {

            $registered_sidebars = wp_get_sidebars_widgets();
            $sidebars_widgets    = get_option('sidebars_widgets', []);
            $deregister_ts       = time();

            foreach ($registered_sidebars as $sidebar_id => $widgets) {
                $sidebars_widgets[$sidebar_id] = [];
                update_option($sidebar_id . '-deregistered', 'yes');
                update_option($sidebar_id . '-deregistered-timestamp', $deregister_ts);
            }
            update_option('sidebars_widgets', $sidebars_widgets);

            $active_theme = get_option('template');
            $sPath = ( $active_theme === 'aios-starter-theme' )
                ? get_stylesheet_directory_uri()
                : get_template_directory_uri();

            $data = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('config.json');

            $widgets = $data->widgets;

            $all_widget_opts = [];

            foreach ($widgets as $widget_info) {

                if (isset($widget_info->args->pbcw_category)) {
                    $widget_info->args->pbcw_category = get_cat_ID('Blog');
                }

                $widget_args = [];
                foreach ($widget_info->args as $arg_key => $arg_value) {
                    $widget_args[$arg_key] = $arg_value;
                }

                $this->widgetGeneratorBatch(
                    $sidebars_widgets,
                    $all_widget_opts,
                    $widget_info->id,
                    $widget_info->type,
                    $widget_args,
                );
            }

            update_option('sidebars_widgets', $sidebars_widgets);
            foreach ($all_widget_opts as $wname => $wopts) {
                update_option("widget_$wname", $wopts);
            }

            $communitiesConfig = $data->config[0]->plugins->aios_communities;
            $agentsConfig = $data->config[0]->plugins->aios_agents;
            $client_info = $data->config[0]->site_info;
            $roadmapsConfig = $data->config[0]->plugins->aios_roadmaps;
            $testimonialsConfig = $data->config[0]->plugins->aios_testimonials;
            $listingsConfig = $data->config[0]->plugins->aios_listings;
            $ihfConfig = $data->config[0]->plugins->aios_custom_ihf;
            $homevaluation = $data->config[0]->plugins->aios_homevaluation;
            /// Plugins Settings

            // Homevaluation
            $home_valuation_settings = get_option('aios_home_valuation_settings');

            if($homevaluation->background){
                $homevaluation_path = $sPath . '/' . $homevaluation->extension . '/images/' . $homevaluation->background;
                $home_valuation_background = media_sideload_image( $homevaluation_path, '0', '', 'id');
                $home_valuation_settings['background_image'] = $home_valuation_background;
            }

            if($homevaluation->agent_photo){
                $homevaluation_agent_path = $sPath . '/' . $homevaluation->extension . '/images/' . $homevaluation->agent_photo;
                $home_valuation_agent_photo = media_sideload_image( $homevaluation_agent_path, '0', '', 'id');
                $home_valuation_settings['agent_photo'] = $home_valuation_agent_photo;
            }

            foreach ($homevaluation as $key => $value) {
                if (in_array($key, ['extension', 'background', 'agent_photo'])) {
                    continue;
                }
                $home_valuation_settings[$key] = $value;
            }
            update_option('aios_home_valuation_settings', $home_valuation_settings);


            // Testimonials
            $testimonials_options = get_option('aios_testimonials_settings');
            $testimonial_page = get_page_by_title('Testimonials');

            $testimonials_options['main_page'] = $testimonial_page->ID ;

            foreach ($testimonialsConfig as $key => $value) {

                if ($key === 'primary_color') {
                    $testimonials_options[$key] = $client_info->primary_color;
                    continue;
                }
                if ($key === 'main_page' || $key === 'theme') {
                    continue;
                }
                $testimonials_options[$key] = $value;
            }

            update_option('aios_testimonials_settings', $testimonials_options);

            // Communities
            $aiosCommunities = get_option('aios_communities_settings');
            $get_communities_page = get_page_by_title('Communities');

            $aiosCommunities['main_page'] = $get_communities_page->ID;

            foreach ($communitiesConfig as $key => $value) {

                if ($key === 'primary_color') {
                    $aiosCommunities[$key] = $client_info->primary_color;
                    continue;
                }
                if ($key === 'main_page' || $key === 'theme') {
                    continue;
                }
                $aiosCommunities[$key] = $value;
            }

            update_option('aios_communities_settings', $aiosCommunities);


            // Agents
            $agents = get_option('agents_settings');
            $get_agents_page = '';

            if ($get_agents_page) {
                $get_agents_page = get_page_by_title('Meet The Team');
                ;
            } else {
                $get_agents_page = get_page_by_title($agentsConfig->page_title ?? 'Our Team');
            }

            $agents['main_page'] = $get_agents_page->ID;


            foreach ($agentsConfig as $key => $value) {

                if ($key === 'main_page') {
                    continue;
                }
                if ($key === 'primary_color' && !empty($value)) {
                    $agents[$key] = $agentsConfig->$key ?? $client_info->primary_color;

                }
                if ($key === 'secondary_color' && !empty($value)) {
                    $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? "#000000";
                }
                if ($key === 'text_color' && !empty($value)) {
                    $agents[$key] = $agentsConfig->$key ?? '#000000';

                }
                if ($key === 'secondary_text_color' && !empty($value)) {
                    $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? "#000000";

                }
                if ($key === 'icon_color' && !empty($value)) {
                    $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? $client_info->primary_color;
                    ;

                }
                if ($key === 'hover_color' && !empty($value)) {
                    $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? $client_info->primary_color;
                    ;

                }
                $agents[$key] = $value;

            }
            update_option('agents_settings', $agents);

            // Roadmaps
            $aiosRoadmaps = get_option('aios_roadmaps_settings');

            foreach ($roadmapsConfig as $key => $value) {
                if ($key === 'primary_color') {
                    $aiosRoadmaps[$key] = $roadmapsConfig->$key ?? $client_info->primary_color;
                }
                if ($key === 'secondary_color') {
                    $aiosRoadmaps[$key] = $roadmapsConfig->$key ?? $client_info->secondary_color ?? $client_info->primary_color;
                }
                if ($key === 'hover_color') {
                    $aiosRoadmaps[$key] = $client_info->$key ?? $client_info->hover_color ?? '#000000';
                }
                $aiosRoadmaps[$key] = $value;
            }

            update_option('aios_roadmaps_settings', $aiosRoadmaps);



            if ($data->product_type !== 'AgentImagex') {
                // Listings
                $listings = get_option('listings_settings');
                $get_properties_page = get_page_by_title('Properties');
                $get_properties_featured = get_page_by_title('Featured Listings');
                $listings['main_page'] = $get_properties_page->ID;
                $listings['featured_property_page'] = $get_properties_featured->ID;
                update_option('listings_settings', $listings);

                $listing_options = [
                    'background_overlay'         => 'listings_background_overlay',
                    'background_overlay_opacity' => 'listings_background_overlay_opacity',
                ];

                foreach ($listing_options as $key => $option_name) {
                    if (isset($listingsConfig->$key)) {
                        update_option($option_name, $listingsConfig->$key);
                    }
                }
            }
            // Listings Colors
            update_option('listings_results_page_primary_color', $listingsConfig->primary_color ?? $client_info->primary_color);
            update_option('listings_results_page_secondary_color', $listingsConfig->secondary_color ?? $client_info->secondary_color ?? $client_info->primary_color);
            update_option('listings_results_page_text_color', $listingsConfig->text_color ?? '#000000');



            //aios-roadmaps
            if ($roadmapsConfig->theme) {
                update_option('roadmaps-themes', '' . $roadmapsConfig->theme . '-core');
            }


            // aios-communities
            if (!empty($communitiesConfig->theme)) {
                update_option('communities-themes', "{$communitiesConfig->theme}-core");
            } else {
                // support individual theme page setting
                if (!empty($communitiesConfig->main_page)) {
                    update_option('archive-communities-themes', "{$communitiesConfig->main_page}-core");
                }

                if (!empty($communitiesConfig->details_page)) {
                    update_option('single-communities-themes', "{$communitiesConfig->details_page}-core");
                }
            }

            // aios-agents
            if ($agentsConfig->main_page) {
                update_option('agent-main-page', '' . $agentsConfig->main_page . '-core');
                update_option('agent-details-page', '' . $agentsConfig->details_page . '-core');

            } else {
                update_option('agent-main-page', 'default-core');
                update_option('agent-details-page', 'default-core');
            }

            if ($testimonialsConfig->theme) {
                // aios-testimonials
                $testimonials_theme = $testimonialsConfig->theme . '-core';
                if ($testimonials_theme === 'clarity-core') {
                    $testimonials_theme = 'default-core';
                }
                update_option('testimonials-themes', $testimonials_theme);
            }

            if ($listingsConfig->main_page) {
                update_option('listing-main-page', '' . $listingsConfig->main_page . '-core');
                update_option('listing-details-page', '' . $listingsConfig->details_page . '-core');

            }

            if ($ihfConfig->results_page) {
                update_option('aios-custom-ihomefinder-templates-results-page', '' . $ihfConfig->results_page . '-core');
                update_option('aios-custom-ihomefinder-templates-details-page', '' . $ihfConfig->details_page . '-core');
            }

            // sets permalink custom structure
            update_option('permalink_structure', '/%category%/%postname%/');


            flush_rewrite_rules();


            // Set the option to indicate that pages have been generated
            update_option('aios_auto_population_widgets', true);
            update_option('aios_auto_population_widgets_date', $dateComplete);

            $response_data['status'] = true;
            $response_data['message'] = 'Widgets generated successfully';
            $response_data['date'] = $dateComplete;

        } else {
            $response_data['status'] = false;
            $response_data['message'] = 'Widgets already generated';
            $response_data['date'] = $dateComplete;
        }

        return rest_ensure_response($response_data);
    }
}
new widgets();
