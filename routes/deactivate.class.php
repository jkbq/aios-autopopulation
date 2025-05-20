<?php

namespace AIOS\AUTOPOPULATE\Routes;

use AIOS\AUTOPOPULATE\Helpers\Helpers;

class DEACTIVATE_PLUGIN
{
    public function __construct()
    {
        add_action( 'rest_api_init', [$this, 'register_endpoints'] );
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/deactivate', [
            'methods'   => 'POST',
            'callback'  => [ $this, 'aios_populate_decativate_plugins' ],
        ]);
    }

    public function aios_populate_decativate_plugins($data)
    {
        // Specify the plugin slug (e.g., 'akismet/akismet.php')
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