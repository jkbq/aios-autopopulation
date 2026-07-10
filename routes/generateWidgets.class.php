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

    public function aios_populate_contents($data)
    {
        $repopulate = ! empty($data['repopulate']);
        $date       = $data['date'] ?? '';

        $response_data = \AIOS\AUTOPOPULATE\Services\PluginSettingsPopulator::run($repopulate, $date);

        return rest_ensure_response($response_data);
    }
}
new Widgets();
