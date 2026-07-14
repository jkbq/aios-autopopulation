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

    const CANNED_MODIFIED_BASELINE_META = '_aios_canned_modified_baseline';

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
     * Stamp the current post_modified_gmt after a canned item is fully generated.
     */
    public static function mark_canned_content_baseline( int $post_id ): void
    {
        if ( $post_id <= 0 ) {
            return;
        }

        // Ensure we read the latest modified timestamp after thumbnails/meta updates.
        clean_post_cache( $post_id );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return;
        }

        update_post_meta( $post_id, self::CANNED_MODIFIED_BASELINE_META, $post->post_modified_gmt );
    }

    /**
     * Whether a post has a real (non-autosave) revision — strong signal of a user edit.
     */
    public static function canned_content_has_revisions( int $post_id ): bool
    {
        if ( $post_id <= 0 || ! post_type_supports( get_post_type( $post_id ), 'revisions' ) ) {
            return false;
        }

        $revisions = wp_get_post_revisions( $post_id, [
            'check_enabled' => true,
        ] );

        if ( empty( $revisions ) ) {
            return false;
        }

        foreach ( $revisions as $revision ) {
            if ( wp_is_post_autosave( $revision ) ) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Whether a tracked canned post is still unmodified since generation.
     *
     * Checked in real time from post data: revisions, _edit_last, and modified-date baseline.
     */
    public static function is_canned_content_unmodified( int $post_id ): bool
    {
        if ( $post_id <= 0 ) {
            return false;
        }

        $post = get_post( $post_id );

        if ( ! $post ) {
            return false;
        }

        if ( self::canned_content_has_revisions( $post_id ) ) {
            return false;
        }

        if ( ! empty( get_post_meta( $post_id, '_edit_last', true ) ) ) {
            return false;
        }

        $baseline = get_post_meta( $post_id, self::CANNED_MODIFIED_BASELINE_META, true );

        if ( $baseline !== '' && $baseline !== false && $baseline !== null ) {
            return (string) $post->post_modified_gmt === (string) $baseline;
        }

        // Legacy items with no baseline and no edit markers: treat as unmodified.
        return true;
    }

    /**
     * Split tracked IDs into unmodified vs edited.
     *
     * @param int[] $ids
     * @return array{unmodified: int[], edited: int[]}
     */
    public static function classify_canned_content_ids( array $ids ): array
    {
        $unmodified = [];
        $edited     = [];

        foreach ( $ids as $id ) {
            $id = absint( $id );

            if ( $id <= 0 ) {
                continue;
            }

            if ( ! get_post( $id ) ) {
                continue;
            }

            if ( self::is_canned_content_unmodified( $id ) ) {
                $unmodified[] = $id;
            } else {
                $edited[] = $id;
            }
        }

        return [
            'unmodified' => $unmodified,
            'edited'     => $edited,
        ];
    }

    /**
     * Delete tracked canned posts for a section.
     *
     * @param bool $unmodified_only When true (default), preserve edited posts.
     * @return array{deleted: int, unmodified: int, edited: int, remaining: int[]}
     */
    public static function delete_canned_content_by_ids( string $ids_option, string $post_type, bool $unmodified_only = true ): array
    {
        $ids        = self::get_canned_content_ids( $ids_option );
        $classified = self::classify_canned_content_ids( $ids );
        $deleted    = 0;

        if ( $unmodified_only ) {
            $to_delete = $classified['unmodified'];
            $remaining = $classified['edited'];
        } else {
            $to_delete = $ids;
            $remaining = [];
        }

        foreach ( $to_delete as $id ) {
            if ( $id > 0 && get_post_type( $id ) === $post_type && wp_delete_post( $id, true ) ) {
                $deleted++;
            } elseif ( $unmodified_only && $id > 0 && get_post( $id ) ) {
                // Keep any unmodified ID that failed to delete.
                $remaining[] = $id;
            }
        }

        $remaining = array_values( array_unique( array_map( 'absint', $remaining ) ) );

        if ( empty( $remaining ) ) {
            delete_option( $ids_option );
        } else {
            update_option( $ids_option, $remaining );
        }

        return [
            'deleted'    => $deleted,
            'unmodified' => count( $classified['unmodified'] ),
            'edited'     => count( $classified['edited'] ),
            'remaining'  => $remaining,
        ];
    }

    /**
     * Delete canned content for one section.
     *
     * @param bool $unmodified_only When true (default), preserve edited posts and keep status if any remain.
     * @return array{deleted: int, section: string, count: int, unmodified: int, edited: int, remaining: int}
     */
    public static function delete_canned_content_section( string $slug, bool $unmodified_only = true ): array
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
                'deleted'    => 0,
                'section'    => $slug,
                'count'      => 0,
                'unmodified' => 0,
                'edited'     => 0,
                'remaining'  => 0,
            ];
        }

        $result    = self::delete_canned_content_by_ids( $config['ids_option'], $config['post_type'], $unmodified_only );
        $remaining = count( $result['remaining'] );

        if ( $remaining === 0 ) {
            delete_option( $config['status_option'] );
            delete_option( $config['date_option'] );
        }

        return [
            'deleted'    => $result['deleted'],
            'section'    => $slug,
            'count'      => $remaining,
            'unmodified' => $result['unmodified'],
            'edited'     => $result['edited'],
            'remaining'  => $remaining,
        ];
    }

    /**
     * Prepare a canned section for repopulate: remove unmodified only, keep edited, clear status so generate can run.
     *
     * @return array{deleted: int, section: string, count: int, unmodified: int, edited: int, remaining: int}
     */
    public static function prepare_canned_section_for_repopulate( string $slug ): array
    {
        $result = self::delete_canned_content_section( $slug, true );

        foreach ( self::canned_content_sections() as $section ) {
            if ( $section['slug'] === $slug ) {
                delete_option( $section['status_option'] );
                delete_option( $section['date_option'] );
                break;
            }
        }

        return $result;
    }

    /**
     * Persist canned IDs, optionally merging with remaining (edited) IDs already stored.
     *
     * @param int[] $new_ids
     */
    public static function store_canned_content_ids( string $ids_option, array $new_ids, bool $merge_existing = false ): void
    {
        $ids = array_map( 'absint', $new_ids );

        if ( $merge_existing ) {
            $ids = array_merge( self::get_canned_content_ids( $ids_option ), $ids );
        }

        $ids = array_values( array_unique( array_filter( $ids ) ) );

        if ( empty( $ids ) ) {
            delete_option( $ids_option );
        } else {
            update_option( $ids_option, $ids );
        }
    }

    /**
     * Delete unmodified canned content across all tracked sections.
     *
     * @return array<string, array{deleted: int, section: string, count: int, unmodified: int, edited: int, remaining: int}>
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
     *
     * @return array<string, array{count: int, unmodified: int, edited: int}>
     */
    public static function canned_content_counts(): array
    {
        $counts = [];

        foreach ( self::canned_content_sections() as $name => $section ) {
            $ids        = self::get_canned_content_ids( $section['ids_option'] );
            $classified = self::classify_canned_content_ids( $ids );

            $counts[ $name ] = [
                'count'      => count( $ids ),
                'unmodified' => count( $classified['unmodified'] ),
                'edited'     => count( $classified['edited'] ),
            ];
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
            $section_counts = $counts[ $name ] ?? [
                'count'      => 0,
                'unmodified' => 0,
                'edited'     => 0,
            ];

            $rows[] = [
                'name'       => $name,
                'slug'       => $section['slug'],
                'count'      => $section_counts['count'],
                'unmodified' => $section_counts['unmodified'],
                'edited'     => $section_counts['edited'],
                'generated'  => ! empty( $api_status[ $name ]['status'] ),
                'repop_slug' => sanitize_title( $name ),
            ];
        }

        return $rows;
    }

    /**
     * Canned content counts for live admin UI updates.
     */
    public static function canned_content_status_payload(): array
    {
        $rows            = self::canned_content_rows();
        $total           = 0;
        $total_unmodified = 0;
        $total_edited    = 0;
        $sections        = [];

        foreach ( $rows as $row ) {
            $total            += (int) $row['count'];
            $total_unmodified += (int) $row['unmodified'];
            $total_edited     += (int) $row['edited'];
            $sections[ $row['slug'] ] = [
                'count'      => (int) $row['count'],
                'unmodified' => (int) $row['unmodified'],
                'edited'     => (int) $row['edited'],
                'generated'  => (bool) $row['generated'],
                'repop_slug' => $row['repop_slug'],
                'can_delete' => (int) $row['unmodified'] > 0,
            ];
        }

        return [
            'total'      => $total,
            'unmodified' => $total_unmodified,
            'edited'     => $total_edited,
            'sections'   => $sections,
        ];
    }

}
