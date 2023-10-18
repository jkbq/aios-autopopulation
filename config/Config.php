<?php

namespace AiosAutoPopulate\Config;

trait Config {

    public function authenticateRequest($api_key_from_request) {
        // Validate the API key
        if ($api_key_from_request !== $this->api_key) {
            return new \WP_Error('rest_forbidden', esc_html__('Invalid API Key', 'your-text-domain'), array('status' => 401));
        }

        return true;
    }

}
