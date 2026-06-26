<?php

namespace AIOS\AUTOPOPULATE\Helpers;

class Helpers
{
    /**
     * @param $themes
     * @return
     */
    public function agentpro_themes()
    {

        $themes = [
            "AgentPro Galaxy",
            "AgentPro Beacon",
            "AgentPro Panorama",
            "AgentPro Radiance",
            "AgentPro Purist",
            "AgentPro Endeavor",
            "AgentPro Legacy",
            "AgentPro Amante II",
            "AgentPro Element",
            "AgentPro Iconic",
            "AgentPro Vega",
            "AgentPro Maven",
            "AgentPro Metropolitan",
            "AgentPro Equinox",
            "AgentPro Ascend",
            "AgentPro Elevate",
            "AgentPro Mobile Iconic",
            "AgentPro Mobile Vega",
            "AgentPro Mobile Metropolitan",
            "AgentPro Mobile Radiance",
            "AgentPro Mobile Purist",
            "AgentPro Mobile Endeavor",
            "AgentPro Mobile Galaxy",
            "AgentPro Mobile Panorama",
            "AgentPro Mobile Maven",
            "AIX Royale",
            "AIX Quantum",
            "AIX Seneca",
            "AIX Hamilton",
        ];

        return $themes;
    }

    public static function is_aios_theme( string $slug = '' ): bool
    {
        if ( $slug === '' ) {
            $slug = get_option('template', '');
            if ( $slug === 'aios-starter-theme' ) {
                $slug = get_option('stylesheet', $slug);
            }
        }
        return (bool) preg_match('/^(agentpro-|aix-|aios-)/', $slug);
    }

    /**
     * @param $themes
     * @return
     */
    public function api_status()
    {
        $p = 'aios_auto_population_';

        $apiStatus = [
            "Settings"          => [ "status" => get_option($p . 'initial_setup_assets'),       "date" => get_option($p . 'initial_setup_assets_date') ],
            "Default Pages"     => [ "status" => get_option($p . 'default_pages'),              "date" => get_option($p . 'default_pages_date') ],
            "Forms"             => [ "status" => get_option($p . 'form'),                        "date" => get_option($p . 'form_date') ],
            "Page"              => [ "status" => get_option($p . 'page'),                        "date" => get_option($p . 'page_date') ],
            "Post"              => [ "status" => get_option($p . 'post'),                        "date" => get_option($p . 'post_date') ],
            "Testimonials"      => [ "status" => get_option($p . 'testimonials'),               "date" => get_option($p . 'testimonials_date') ],
            "Communities"       => [ "status" => get_option($p . 'communities'),                "date" => get_option($p . 'communities_date') ],
            "Agents"            => [ "status" => get_option($p . 'agents'),                     "date" => get_option($p . 'agents_date') ],
            "Listings"          => [ "status" => get_option($p . 'listings'),                   "date" => get_option($p . 'listings_date') ],
            "Buyers"            => [ "status" => get_option($p . 'roadmaps_buyers'),            "date" => get_option($p . 'roadmaps_buyers_date') ],
            "Sellers"           => [ "status" => get_option($p . 'roadmaps_sellers'),           "date" => get_option($p . 'roadmaps_sellers_date') ],
            "Financing"         => [ "status" => get_option($p . 'roadmaps'),                   "date" => get_option($p . 'roadmaps_date') ],
            "About and Contact" => [ "status" => get_option($p . 'about_contact_generate'),     "date" => get_option($p . 'about_contact_generate_date') ],
            "Slideshow"         => [ "status" => get_option($p . 'slider'),                     "date" => get_option($p . 'slider_date') ],
            "Menu"              => [ "status" => get_option($p . 'menu'),                       "date" => get_option($p . 'menu_date') ],
            "Widgets"           => [ "status" => get_option($p . 'widgets'),                    "date" => get_option($p . 'widgets_date') ],
        ];

        return $apiStatus;
    }

    /**
     * @param $themes
     * @return
     */
    public function data($json)
    {

        $jsonData =  get_stylesheet_directory_uri() . '/' . $json;

        $response = wp_remote_get($jsonData, [
            'timeout' => 45,
            'blocking' => true,
            'cookies' => [],
        ]);

        return $response;
    }

    /**
     * Fetch and transient-cache a theme JSON file. Returns decoded object or null on error.
     */
    public static function get_theme_json( string $filename, int $ttl = 3600 ): ?object
    {
        $cache_key = 'aios_theme_json_' . sanitize_key( $filename );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) {
            return $cached;
        }

        $active_theme = get_option('template');
        $sPath        = ( $active_theme === 'aios-starter-theme' )
            ? get_stylesheet_directory_uri()
            : get_template_directory_uri();

        $response = wp_remote_get( $sPath . '/' . $filename, [
            'timeout'  => 45,
            'blocking' => true,
            'cookies'  => [],
        ]);

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $data = json_decode( $response['body'] );
        set_transient( $cache_key, $data, $ttl );
        return $data;
    }

    /**
     * Delete all cached theme JSON transients (call before repopulate or deactivate).
     */
    public static function clear_theme_json_cache(): void
    {
        delete_transient('aios_theme_json_configjson');
        delete_transient('aios_theme_json_contentsjson');
        delete_transient('aios_theme_json_defaultjson');
    }

    /**
     * Read and transient-cache a local plugin JSON file using file_get_contents (no HTTP).
     */
    public static function get_local_json( string $file_path, int $ttl = 3600 ): ?object
    {
        $cache_key = 'aios_local_json_' . md5( $file_path );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) {
            return $cached;
        }
        if ( ! file_exists( $file_path ) ) {
            return null;
        }
        $json = file_get_contents( $file_path );
        if ( $json === false ) {
            return null;
        }
        $data = json_decode( $json );
        set_transient( $cache_key, $data, $ttl );
        return $data;
    }

    /**
     * Return an existing attachment ID whose source URL matches, or 0 if none found.
     */
    public static function get_attachment_by_source_url( string $url ): int
    {
        global $wpdb;
        $id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_source_url' AND meta_value = %s LIMIT 1",
            $url
        ));
        return (int) $id;
    }

    /**
     * Sanitize the core fields of a post data array before wp_insert_post().
     */
    public static function sanitize_post_data( array $data ): array
    {
        return [
            'post_title'    => sanitize_text_field( $data['post_title']   ?? '' ),
            'post_content'  => wp_kses_post(         $data['post_content'] ?? '' ),
            'post_type'     => sanitize_key(          $data['post_type']   ?? 'post' ),
            'post_status'   => sanitize_key(          $data['post_status'] ?? 'publish' ),
            'post_author'   => absint(                $data['post_author'] ?? 1 ),
        ];
    }

}
