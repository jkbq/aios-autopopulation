<?php

namespace AIOS\AUTOPOPULATE\Routes;

class Status
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_status'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin'],
        ]);
    }

    public function get_status()
    {
        $helpers = new \AIOS\AUTOPOPULATE\Helpers\Helpers();
        return rest_ensure_response($helpers->api_status());
    }
}
new Status();
