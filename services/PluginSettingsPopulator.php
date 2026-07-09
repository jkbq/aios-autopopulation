<?php

namespace AIOS\AUTOPOPULATE\Services;

class PluginSettingsPopulator
{
    /**
     * @return array{status: bool, message: string, date: string}
     */
    public static function run(bool $repopulate = false, string $date = ''): array
    {
        if ($repopulate) {
            PopulateService::clearCache();
            delete_option('aios_auto_population_widgets');
            delete_option('aios_auto_population_widgets_date');
        }

        $dateComplete      = get_option('aios_auto_population_widgets_date', $date);
        $widgets_generated = get_option('aios_auto_population_widgets', false);

        if ($widgets_generated) {
            return [
                'status'  => false,
                'message' => 'Widgets already generated',
                'date'    => $dateComplete,
            ];
        }

        $registered_sidebars = wp_get_sidebars_widgets();
        $sidebars_widgets    = get_option('sidebars_widgets', []);
        $deregister_ts       = time();

        foreach ($registered_sidebars as $sidebar_id => $widgets) {
            $sidebars_widgets[$sidebar_id] = [];
            update_option($sidebar_id . '-deregistered', 'yes');
            update_option($sidebar_id . '-deregistered-timestamp', $deregister_ts);
        }
        update_option('sidebars_widgets', $sidebars_widgets);

        $sPath  = PopulateService::getThemeUri();
        $config = PopulateService::getConfig();

        if (! $config || empty($config->widgets)) {
            return [
                'status'  => false,
                'message' => 'Error fetching widget config',
                'date'    => $dateComplete,
            ];
        }

        $all_widget_opts = [];

        foreach ($config->widgets as $widget_info) {
            if (isset($widget_info->args->pbcw_category)) {
                $widget_info->args->pbcw_category = get_cat_ID('Blog');
            }

            $widget_args = [];
            foreach ($widget_info->args as $arg_key => $arg_value) {
                $widget_args[$arg_key] = $arg_value;
            }

            self::widgetGeneratorBatch(
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

        self::applyPluginSettings($config, $sPath);

        update_option('aios_auto_population_widgets', true);
        update_option('aios_auto_population_widgets_date', $dateComplete);

        return [
            'status'  => true,
            'message' => 'Widgets generated successfully',
            'date'    => $dateComplete,
        ];
    }

    private static function widgetGeneratorBatch(array &$sidebars, array &$all_widget_opts, string $sidebar, string $name, array $args = []): void
    {
        if (! isset($sidebars[$sidebar])) {
            $sidebars[$sidebar] = [];
        }

        if (! isset($all_widget_opts[$name])) {
            $widget_opts = get_option("widget_$name", []);
            if (! $widget_opts || (count($widget_opts) === 1 && isset($widget_opts['_multiwidget']))) {
                $widget_opts = ['_multiwidget' => 1];
            }
            $all_widget_opts[$name] = $widget_opts;
        }

        $keys      = array_map('intval', array_keys($all_widget_opts[$name]));
        $insert_id = max($keys) + 1;

        $all_widget_opts[$name][$insert_id] = $args;
        $sidebars[$sidebar][]               = $name . '-' . $insert_id;
    }

    private static function applyPluginSettings(object $config, string $sPath): void
    {
        $communitiesConfig  = $config->config[0]->plugins->aios_communities;
        $agentsConfig       = $config->config[0]->plugins->aios_agents;
        $client_info        = $config->config[0]->site_info;
        $roadmapsConfig     = $config->config[0]->plugins->aios_roadmaps;
        $testimonialsConfig = $config->config[0]->plugins->aios_testimonials;
        $listingsConfig     = $config->config[0]->plugins->aios_listings;
        $ihfConfig          = $config->config[0]->plugins->aios_custom_ihf;
        $homevaluation      = $config->config[0]->plugins->aios_homevaluation;

        $home_valuation_settings = get_option('aios_home_valuation_settings', []);

        if (! empty($homevaluation->background)) {
            $homevaluation_path = $sPath . '/' . $homevaluation->extension . '/images/' . $homevaluation->background;
            $home_valuation_settings['background_image'] = media_sideload_image($homevaluation_path, '0', '', 'id');
        }

        if (! empty($homevaluation->agent_photo)) {
            $homevaluation_agent_path = $sPath . '/' . $homevaluation->extension . '/images/' . $homevaluation->agent_photo;
            $home_valuation_settings['agent_photo'] = media_sideload_image($homevaluation_agent_path, '0', '', 'id');
        }

        foreach ($homevaluation as $key => $value) {
            if (in_array($key, ['extension', 'background', 'agent_photo'], true)) {
                continue;
            }
            $home_valuation_settings[$key] = $value;
        }
        update_option('aios_home_valuation_settings', $home_valuation_settings);

        $testimonials_options = get_option('aios_testimonials_settings', []);
        $testimonial_page     = get_page_by_title('Testimonials');
        if ($testimonial_page) {
            $testimonials_options['main_page'] = $testimonial_page->ID;
        }

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

        $aiosCommunities      = get_option('aios_communities_settings', []);
        $get_communities_page = get_page_by_title('Communities');
        if ($get_communities_page) {
            $aiosCommunities['main_page'] = $get_communities_page->ID;
        }

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

        $agents          = get_option('agents_settings', []);
        $get_agents_page = get_page_by_title($agentsConfig->page_title ?? 'Our Team');
        if ($get_agents_page) {
            $agents['main_page'] = $get_agents_page->ID;
        }

        foreach ($agentsConfig as $key => $value) {
            if ($key === 'main_page') {
                continue;
            }
            if ($key === 'primary_color' && ! empty($value)) {
                $agents[$key] = $agentsConfig->$key ?? $client_info->primary_color;
            }
            if ($key === 'secondary_color' && ! empty($value)) {
                $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? '#000000';
            }
            if ($key === 'text_color' && ! empty($value)) {
                $agents[$key] = $agentsConfig->$key ?? '#000000';
            }
            if ($key === 'secondary_text_color' && ! empty($value)) {
                $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? '#000000';
            }
            if ($key === 'icon_color' && ! empty($value)) {
                $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? $client_info->primary_color;
            }
            if ($key === 'hover_color' && ! empty($value)) {
                $agents[$key] = $agentsConfig->$key ?? $client_info->secondary_color ?? $client_info->primary_color;
            }
            $agents[$key] = $value;
        }
        update_option('agents_settings', $agents);

        $aiosRoadmaps = get_option('aios_roadmaps_settings', []);
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

        if (($config->product_type ?? '') !== 'AgentImagex') {
            $listings                = get_option('listings_settings', []);
            $get_properties_page     = get_page_by_title('Properties');
            $get_properties_featured = get_page_by_title('Featured Listings');
            if ($get_properties_page) {
                $listings['main_page'] = $get_properties_page->ID;
            }
            if ($get_properties_featured) {
                $listings['featured_property_page'] = $get_properties_featured->ID;
            }
            update_option('listings_settings', $listings);

            foreach ([
                'background_overlay'         => 'listings_background_overlay',
                'background_overlay_opacity' => 'listings_background_overlay_opacity',
            ] as $key => $option_name) {
                if (isset($listingsConfig->$key)) {
                    update_option($option_name, $listingsConfig->$key);
                }
            }
        }

        update_option('listings_results_page_primary_color', $listingsConfig->primary_color ?? $client_info->primary_color);
        update_option('listings_results_page_secondary_color', $listingsConfig->secondary_color ?? $client_info->secondary_color ?? $client_info->primary_color);
        update_option('listings_results_page_text_color', $listingsConfig->text_color ?? '#000000');

        if (! empty($roadmapsConfig->theme)) {
            update_option('roadmaps-themes', $roadmapsConfig->theme . '-core');
        }

        if (! empty($communitiesConfig->theme)) {
            update_option('communities-themes', "{$communitiesConfig->theme}-core");
        } else {
            if (! empty($communitiesConfig->main_page)) {
                update_option('archive-communities-themes', "{$communitiesConfig->main_page}-core");
            }
            if (! empty($communitiesConfig->details_page)) {
                update_option('single-communities-themes', "{$communitiesConfig->details_page}-core");
            }
        }

        if (! empty($agentsConfig->main_page)) {
            update_option('agent-main-page', $agentsConfig->main_page . '-core');
            update_option('agent-details-page', $agentsConfig->details_page . '-core');
        } else {
            update_option('agent-main-page', 'default-core');
            update_option('agent-details-page', 'default-core');
        }

        if (! empty($testimonialsConfig->theme)) {
            $testimonials_theme = $testimonialsConfig->theme . '-core';
            if ($testimonials_theme === 'clarity-core') {
                $testimonials_theme = 'default-core';
            }
            update_option('testimonials-themes', $testimonials_theme);
        }

        if (! empty($listingsConfig->main_page)) {
            update_option('listing-main-page', $listingsConfig->main_page . '-core');
            update_option('listing-details-page', $listingsConfig->details_page . '-core');
        }

        if (! empty($ihfConfig->results_page)) {
            update_option('aios-custom-ihomefinder-templates-results-page', $ihfConfig->results_page . '-core');
            update_option('aios-custom-ihomefinder-templates-details-page', $ihfConfig->details_page . '-core');
        }

        update_option('permalink_structure', '/%category%/%postname%/');
        flush_rewrite_rules();
    }
}
