<?php

namespace AIOS\AUTOPOPULATE\Routes;

class Testimonials
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/testimonials', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_testimonials'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_testimonials($data)
    {
        $is_repopulate = ! empty($data['repopulate']);

        if ($is_repopulate) {
            \AIOS\AUTOPOPULATE\Helpers\Helpers::delete_canned_content_section('testimonials');
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        }

        $dateComplete    = get_option('aios_auto_population_testimonials_date', $data['date'] ?? '');
        $pages_generated = get_option('aios_auto_population_testimonials', false);

        $response_data = [
            'date' => $dateComplete,
        ];

        $active_theme = get_option('template');
        $sPath = ( $active_theme === 'aios-starter-theme' )
            ? get_stylesheet_directory_uri()
            : get_template_directory_uri();

        $contents = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('contents.json');

        if (! $contents) {
            $response_data['status']  = 'error';
            $response_data['message'] = 'Error fetching JSON data';

            return rest_ensure_response($response_data);
        }

        $this->apply_testimonials_theme_from_config();

        // Section templates are always retained — only create missing ones.
        if (! empty($contents->{'aios-section-testimonials'})) {
            $this->populate_testimonial_sections($contents->{'aios-section-testimonials'});
        }

        if ($is_repopulate) {
            $generated_ids = [];

            if (! empty($contents->{'aios-testimonials'})) {
                $generated_ids = $this->populate_testimonial_posts($contents->{'aios-testimonials'}, $sPath);
            }

            update_option('aios_auto_population_testimonials_ids', $generated_ids);
            update_option('aios_auto_population_testimonials', true);
            update_option('aios_auto_population_testimonials_date', $data['date'] ?? $dateComplete);

            $response_data['date']    = $data['date'] ?? $dateComplete;
            $response_data['status']  = 'success';
            $response_data['message'] = 'Testimonials repopulated successfully';
        } elseif (! $pages_generated) {
            $generated_ids = [];

            if (! empty($contents->{'aios-testimonials'})) {
                $generated_ids = $this->populate_testimonial_posts($contents->{'aios-testimonials'}, $sPath);
            }

            update_option('aios_auto_population_testimonials_ids', $generated_ids);
            update_option('aios_auto_population_testimonials', true);
            update_option('aios_auto_population_testimonials_date', $dateComplete);

            $response_data['status']  = 'success';
            $response_data['message'] = 'Testimonials generated successfully';
        } else {
            $response_data['status']  = 'success';
            $response_data['message'] = 'Testimonials already generated';
        }

        return rest_ensure_response($response_data);
    }

    private function find_section_by_unique_id(string $unique_id): int
    {
        $sections = get_posts([
            'post_type'      => 'aios-testi-section',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'custom_unique_id',
                    'value'   => $unique_id,
                    'compare' => '=',
                ],
            ],
        ]);

        return ! empty($sections) ? (int) $sections[0] : 0;
    }

    private function populate_testimonial_sections(array $items): void
    {
        foreach ($items as $item) {
            $post_meta = $item->meta_input[0] ?? null;
            $unique_id = $post_meta->testimonials_section_id ?? null;

            if (empty($unique_id)) {
                $unique_id = aiosnexus_generate_unique_id('custom_unique_id');
            }

            if (empty($post_meta->review_source)) {
                continue;
            }

            if ($this->find_section_by_unique_id($unique_id)) {
                continue;
            }

            $review_source = $post_meta->review_source;
            $content_fixed = $item->post_content ?? '';

            $post_data = [
                'post_type'    => sanitize_key($item->post_type),
                'post_title'   => sanitize_text_field($item->post_title),
                'post_content' => wp_kses_post($content_fixed),
                'post_status'  => 'publish',
                'post_author'  => 1,
                'meta_input'   => [
                    'review_source'    => sanitize_text_field($review_source),
                    'custom_unique_id' => sanitize_text_field($unique_id),
                ],
            ];

            wp_insert_post($post_data);
        }
    }

    /**
     * @return int[] Inserted testimonial post IDs.
     */
    private function populate_testimonial_posts(array $items, string $sPath): array
    {
        $generated_ids = [];

        foreach ($items as $value) {
            if ($value->post_type === 'aios-testimonials') {
                $aios_client_info = get_option('aiis_ci');
                $contentData = str_replace('ai_client_name', $aios_client_info['name'] ?? '', $value->post_content);
            } else {
                $contentData = $value->post_content;
            }

            $post_meta = $value->meta_input[0];

            $post_data = [
                'post_type'    => sanitize_key($value->post_type),
                'post_title'   => sanitize_text_field($value->post_title),
                'post_content' => wp_kses_post($contentData),
                'post_status'  => 'publish',
                'post_author'  => 1,
                'meta_input'   => [
                    'aios_testimonials_video_url'  => esc_url_raw($post_meta->aios_testimonials_video_url ?? ''),
                    'aios_testimonials_video_type' => sanitize_text_field($post_meta->aios_testimonials_video_type ?? ''),
                    'aios_testimonials_featured'   => sanitize_text_field($post_meta->aios_testimonials_featured ?? ''),
                ],
            ];

            $insert_post = wp_insert_post($post_data);

            if (! $insert_post || is_wp_error($insert_post)) {
                continue;
            }

            $generated_ids[] = (int) $insert_post;

            if (isset($post_meta->aios_testimonials_video_placeholder)) {
                $extension  = ! empty($post_meta->extension) ? $post_meta->extension . '/' : '';
                $image_url  = $sPath . '/' . $extension . 'images/' . $post_meta->aios_testimonials_video_placeholder;
                $existing   = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_attachment_by_source_url($image_url);
                $image_data = $existing > 0 ? $existing : media_sideload_image($image_url, $insert_post, '', 'id');
                update_post_meta($insert_post, 'aios_testimonials_video_placeholder', $image_data);
            }
        }

        return $generated_ids;
    }

    /**
     * Map testimonials theme from config; equinox themes use the default template.
     */
    private function apply_testimonials_theme_from_config(): void
    {
        $config = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('config.json');

        if (empty($config->config[0]->plugins->aios_testimonials->theme)) {
            return;
        }

        $theme = sanitize_key($config->config[0]->plugins->aios_testimonials->theme);

        if ($theme === 'equinox') {
            $theme = 'default';
        }

        update_option('testimonials-themes', $theme . '-core');
    }
}
new Testimonials();
