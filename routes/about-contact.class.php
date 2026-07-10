<?php

namespace AIOS\AUTOPOPULATE\Routes;

class ABOUT_CONTACT_GENERATE
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/about-contact', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_generate_about_contact'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_generate_about_contact($data)
    {
        $dateComplete = get_option('aios_auto_population_about_contact_generate_date', $data['date'] ?? '');

        $apiResponse = [
            'success' => false,
            'message' => 'About and Contact Failed to Generated',
            'date'    => $dateComplete,
        ];

        $generateAboutContact = get_option('aios_auto_population_about_contact_generate', false);

        if ($generateAboutContact == true) {
            return rest_ensure_response($apiResponse);
        }

        $sPath  = \AIOS\AUTOPOPULATE\Services\PopulateService::getThemeUri();
        $config = \AIOS\AUTOPOPULATE\Services\PopulateService::getConfig();

        if (! $config) {
            $apiResponse['message'] = 'Error fetching theme config';
            return rest_ensure_response($apiResponse);
        }

        $result = \AIOS\AUTOPOPULATE\Services\AboutContactPopulator::runInitial($config, $sPath);

        if ($result['ok']) {
            $apiResponse['success'] = true;
            $apiResponse['message'] = $result['message'];
            $apiResponse['date']    = $dateComplete ?: ($data['date'] ?? '');
            update_option('aios_auto_population_about_contact_generate', true);
            update_option('aios_auto_population_about_contact_generate_date', $apiResponse['date']);
        }

        return rest_ensure_response($apiResponse);
    }
}
new ABOUT_CONTACT_GENERATE();
