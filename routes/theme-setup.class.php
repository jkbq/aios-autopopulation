<?php

namespace AIOS\AUTOPOPULATE\Routes;

class ThemeSetup
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/theme-setup', [
            'methods'             => 'POST',
            'callback'            => [$this, 'run_theme_setup'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin'],
        ]);
    }

    public function run_theme_setup($request)
    {
        $date = $request instanceof \WP_REST_Request
            ? ($request->get_param('date') ?? '')
            : ($request['date'] ?? '');

        if ($date === '') {
            $date = wp_date('Y-m-d H:i:s');
        }

        \AIOS\AUTOPOPULATE\Services\PopulateService::clearCache();

        $widgetsResult = \AIOS\AUTOPOPULATE\Services\PluginSettingsPopulator::run(true, $date);

        $config = \AIOS\AUTOPOPULATE\Services\PopulateService::getConfig();
        $sPath  = \AIOS\AUTOPOPULATE\Services\PopulateService::getThemeUri();

        if (! $config) {
            return \AIOS\AUTOPOPULATE\Services\PopulateService::respond(false, 'Error fetching theme config', $date);
        }

        $aboutContactResult = \AIOS\AUTOPOPULATE\Services\AboutContactPopulator::runRefresh($config, $sPath);

        $active_theme = \AIOS\AUTOPOPULATE\Services\PopulateService::getActiveThemeSlug();
        update_option('aios_autopopulation_theme', $active_theme);
        update_option('aios_auto_population_about_contact_generate', true);
        update_option('aios_auto_population_about_contact_generate_date', $date);

        $message = sprintf(
            'Theme setup applied. %s %s',
            $widgetsResult['message'],
            $aboutContactResult['message']
        );

        return \AIOS\AUTOPOPULATE\Services\PopulateService::respond(true, trim($message), $date, [
            'steps' => [
                'widgets'       => $widgetsResult,
                'about_contact' => $aboutContactResult,
            ],
        ]);
    }
}
new ThemeSetup();
