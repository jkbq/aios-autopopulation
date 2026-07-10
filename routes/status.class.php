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

        register_rest_route('aios-populate/v1', '/canned-content-counts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_canned_content_counts'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin'],
        ]);
    }

    public function get_canned_content_counts()
    {
        return rest_ensure_response(
            \AIOS\AUTOPOPULATE\Helpers\Helpers::canned_content_status_payload()
        );
    }

    public function get_status()
    {
        $helpers = new \AIOS\AUTOPOPULATE\Helpers\Helpers();
        return rest_ensure_response($helpers->api_status());
    }
}
new Status();
