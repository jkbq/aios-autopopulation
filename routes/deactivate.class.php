<?php

namespace AIOS\AUTOPOPULATE\Routes;

class DEACTIVATE_PLUGIN
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/deactivate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'aios_populate_decativate_plugins' ],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_decativate_plugins($data)
    {
        \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        \AIOS\AUTOPOPULATE\Helpers\RestAuth::clear_install_token();

        $plugin_slug = 'aios-autopopulation/aios-autopopulation.php';

        deactivate_plugins($plugin_slug);

        return rest_ensure_response([
            'success' => true,
            'message' => "Site Generation Completed",
            'home' => home_url(),
        ]);
    }
}
new DEACTIVATE_PLUGIN();
