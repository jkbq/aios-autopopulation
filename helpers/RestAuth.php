<?php

namespace AIOS\AUTOPOPULATE\Helpers;

class RestAuth
{
    public static function require_admin(): bool|\WP_Error
    {
        if ( ! current_user_can('manage_options') ) {
            return new \WP_Error('rest_forbidden', 'You do not have permission to perform this action.', ['status' => 403]);
        }
        return true;
    }

    /**
     * Accept either a logged-in admin OR a valid one-time install token.
     * Used on all endpoints called by the frontend install page.
     */
    public static function require_admin_or_install_token( \WP_REST_Request $req ): bool|\WP_Error
    {
        if ( current_user_can('manage_options') ) {
            return true;
        }

        $token  = $req->get_header('X-AIOS-Token') ?? $req->get_param('install_token');
        $stored = get_transient('aios_install_token');

        if ( $stored && $token && hash_equals( (string) $stored, (string) $token ) ) {
            return true;
        }

        return new \WP_Error('rest_forbidden', 'You do not have permission to perform this action.', ['status' => 403]);
    }

    /**
     * Generate (or reuse) a one-time install token, stored for 1 hour.
     */
    public static function generate_install_token(): string
    {
        $existing = get_transient('aios_install_token');
        if ( $existing ) {
            return $existing;
        }
        $token = wp_generate_password(32, false);
        set_transient('aios_install_token', $token, HOUR_IN_SECONDS);
        return $token;
    }

    public static function clear_install_token(): void
    {
        delete_transient('aios_install_token');
    }
}
