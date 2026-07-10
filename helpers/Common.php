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
            "Settings"          => [ "status" => get_option($p . 'initial_setup_assets'),       "date" => get_option($p . 'initial_setup_assets_date'),    "endpoint" => "settings" ],
            "Default Pages"     => [ "status" => get_option($p . 'default_pages'),              "date" => get_option($p . 'default_pages_date'),           "endpoint" => "initial-setup-pages" ],
            "Privacy Policy"    => [ "status" => get_option($p . 'privacy_policy'),           "date" => get_option($p . 'privacy_policy_date'),        "endpoint" => "privacy-policy" ],
            "Forms"             => [ "status" => get_option($p . 'form'),                        "date" => get_option($p . 'form_date'),                    "endpoint" => "form" ],
            "Page"              => [ "status" => get_option($p . 'page'),                        "date" => get_option($p . 'page_date'),                    "endpoint" => "page-populate" ],
            "Post"              => [ "status" => get_option($p . 'post'),                        "date" => get_option($p . 'post_date'),                    "endpoint" => "post-populate" ],
            "Testimonials"      => [ "status" => get_option($p . 'testimonials'),               "date" => get_option($p . 'testimonials_date'),            "endpoint" => "testimonials" ],
            "Communities"       => [ "status" => get_option($p . 'communities'),                "date" => get_option($p . 'communities_date'),             "endpoint" => "communities" ],
            "Agents"            => [ "status" => get_option($p . 'agents'),                     "date" => get_option($p . 'agents_date'),                  "endpoint" => "agents" ],
            "Listings"          => [ "status" => get_option($p . 'listings'),                   "date" => get_option($p . 'listings_date'),                "endpoint" => "listings" ],
            "Buyers"            => [ "status" => get_option($p . 'roadmaps_buyers'),            "date" => get_option($p . 'roadmaps_buyers_date'),         "endpoint" => "roadmaps-buyers" ],
            "Sellers"           => [ "status" => get_option($p . 'roadmaps_sellers'),           "date" => get_option($p . 'roadmaps_sellers_date'),        "endpoint" => "roadmaps-sellers" ],
            "Financing"         => [ "status" => get_option($p . 'roadmaps'),                   "date" => get_option($p . 'roadmaps_date'),                "endpoint" => "roadmaps" ],
            "About and Contact" => [ "status" => get_option($p . 'about_contact_generate'),     "date" => get_option($p . 'about_contact_generate_date'), "endpoint" => "about-contact" ],
            "Slideshow"         => [ "status" => get_option($p . 'slider'),                     "date" => get_option($p . 'slider_date'),                  "endpoint" => "slider" ],
            "Menu"              => [ "status" => get_option($p . 'menu'),                       "date" => get_option($p . 'menu_date'),                    "endpoint" => "menu" ],
            "Widgets"           => [ "status" => get_option($p . 'widgets'),                    "date" => get_option($p . 'widgets_date'),                 "endpoint" => "widgets" ],
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
        $theme_dir    = ( $active_theme === 'aios-starter-theme' )
            ? get_stylesheet_directory()
            : get_template_directory();

        $file_path = $theme_dir . DIRECTORY_SEPARATOR . $filename;
        if ( file_exists( $file_path ) ) {
            $json = file_get_contents( $file_path );
            if ( $json !== false ) {
                $data = json_decode( $json );
                if ( $data !== null ) {
                    set_transient( $cache_key, $data, $ttl );
                    return $data;
                }
            }
        }

        $sPath = ( $active_theme === 'aios-starter-theme' )
            ? get_stylesheet_directory_uri()
            : get_template_directory_uri();

        $response = wp_remote_get( $sPath . '/' . $filename, [
            'timeout'   => 45,
            'blocking'  => true,
            'cookies'   => [],
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $data = json_decode( $response['body'] );
        if ( $data !== null ) {
            set_transient( $cache_key, $data, $ttl );
        }

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
     * Resolve product_type from theme config.json (root or config[0]).
     */
    public static function get_product_type( ?object $config ): string
    {
        if ( ! $config ) {
            return '';
        }

        if ( ! empty( $config->product_type ) ) {
            return (string) $config->product_type;
        }

        if ( ! empty( $config->config[0]->product_type ) ) {
            return (string) $config->config[0]->product_type;
        }

        return '';
    }

    /**
     * Map theme product_type to aios_custom_login_screen option value.
     */
    public static function get_login_screen_slug( string $product_type ): string
    {
        return $product_type === 'AgentImagex' ? 'aix' : 'agentpro';
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

    /**
     * Canned content sections that track generated post IDs for safe deletion.
     */
    public static function canned_content_sections(): array
    {
        $p = 'aios_auto_population_';

        return [
            'Post' => [
                'slug'          => 'post',
                'ids_option'    => $p . 'post_ids',
                'status_option' => $p . 'post',
                'date_option'   => $p . 'post_date',
                'post_type'     => 'post',
            ],
            'Testimonials' => [
                'slug'          => 'testimonials',
                'ids_option'    => $p . 'testimonials_ids',
                'status_option' => $p . 'testimonials',
                'date_option'   => $p . 'testimonials_date',
                'post_type'     => 'aios-testimonials',
            ],
            'Communities' => [
                'slug'          => 'communities',
                'ids_option'    => $p . 'communities_ids',
                'status_option' => $p . 'communities',
                'date_option'   => $p . 'communities_date',
                'post_type'     => 'aios-communities',
            ],
            'Agents' => [
                'slug'          => 'agents',
                'ids_option'    => $p . 'agents_ids',
                'status_option' => $p . 'agents',
                'date_option'   => $p . 'agents_date',
                'post_type'     => 'aios-agents',
            ],
            'Listings' => [
                'slug'          => 'listings',
                'ids_option'    => $p . 'listings_ids',
                'status_option' => $p . 'listings',
                'date_option'   => $p . 'listings_date',
                'post_type'     => 'aios-listings',
            ],
        ];
    }

    /**
     * @return int[] Stored generated post IDs for a canned content section.
     */
    public static function get_canned_content_ids( string $ids_option ): array
    {
        $ids = get_option( $ids_option, [] );

        if ( ! is_array( $ids ) ) {
            return [];
        }

        return array_values( array_filter( array_map( 'absint', $ids ) ) );
    }

    /**
     * Delete tracked canned posts for a section and return the number removed.
     */
    public static function delete_canned_content_by_ids( string $ids_option, string $post_type ): int
    {
        $ids     = self::get_canned_content_ids( $ids_option );
        $deleted = 0;

        foreach ( $ids as $id ) {
            if ( $id > 0 && get_post_type( $id ) === $post_type && wp_delete_post( $id, true ) ) {
                $deleted++;
            }
        }

        delete_option( $ids_option );

        return $deleted;
    }

    /**
     * Delete canned content for one section and reset its population status.
     *
     * @return array{deleted: int, section: string, count: int}
     */
    public static function delete_canned_content_section( string $slug ): array
    {
        $config = null;

        foreach ( self::canned_content_sections() as $section ) {
            if ( $section['slug'] === $slug ) {
                $config = $section;
                break;
            }
        }

        if ( ! $config ) {
            return [
                'deleted' => 0,
                'section' => $slug,
                'count'   => 0,
            ];
        }

        $ids     = self::get_canned_content_ids( $config['ids_option'] );
        $deleted = self::delete_canned_content_by_ids( $config['ids_option'], $config['post_type'] );

        delete_option( $config['status_option'] );
        delete_option( $config['date_option'] );

        return [
            'deleted' => $deleted,
            'section' => $slug,
            'count'   => count( $ids ),
        ];
    }

    /**
     * Delete canned content across all tracked sections.
     *
     * @return array<string, array{deleted: int, section: string, count: int}>
     */
    public static function delete_all_canned_content(): array
    {
        $results = [];

        foreach ( self::canned_content_sections() as $section ) {
            $results[ $section['slug'] ] = self::delete_canned_content_section( $section['slug'] );
        }

        return $results;
    }

    /**
     * Count tracked canned posts per section for the admin UI.
     */
    public static function canned_content_counts(): array
    {
        $counts = [];

        foreach ( self::canned_content_sections() as $name => $section ) {
            $counts[ $name ] = count( self::get_canned_content_ids( $section['ids_option'] ) );
        }

        return $counts;
    }

    /**
     * Admin rows for the Canned Content tab.
     */
    public static function canned_content_rows(): array
    {
        $api_status = ( new self() )->api_status();
        $counts     = self::canned_content_counts();
        $rows       = [];

        foreach ( self::canned_content_sections() as $name => $section ) {
            $rows[] = [
                'name'      => $name,
                'slug'      => $section['slug'],
                'count'     => $counts[ $name ] ?? 0,
                'generated' => ! empty( $api_status[ $name ]['status'] ),
                'repop_slug'=> sanitize_title( $name ),
            ];
        }

        return $rows;
    }

    /**
     * Canned content counts for live admin UI updates.
     */
    public static function canned_content_status_payload(): array
    {
        $rows  = self::canned_content_rows();
        $total = 0;
        $sections = [];

        foreach ($rows as $row) {
            $total += (int) $row['count'];
            $sections[$row['slug']] = [
                'count'      => (int) $row['count'],
                'generated'  => (bool) $row['generated'],
                'repop_slug' => $row['repop_slug'],
                'can_delete' => $row['count'] > 0 || $row['generated'],
            ];
        }

        return [
            'total'    => $total,
            'sections' => $sections,
        ];
    }

}
