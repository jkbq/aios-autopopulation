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

        $dateComplete = get_option('aios_auto_population_testimonials_date', $data['date']);

        $pages_generated = get_option('aios_auto_population_testimonials', false);

        $response_data = [];

        $response_data['date'] = $dateComplete;

        if (!$pages_generated) {

            $active_theme = get_option('template');
            $sPath = ( $active_theme === 'aios-starter-theme' )
                ? get_stylesheet_directory_uri()
                : get_template_directory_uri();

            $contents = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('contents.json');

            if ( ! $contents ) {
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {
                foreach ($contents as $key => $content) {

                    if ($key === 'aios-section-testimonials') {
                        $generated_ids = [];
                        foreach ($content as $item) {
                            $post_meta = $item->meta_input[0] ?? null;
                            $unique_id = $post_meta->testimonials_section_id ?? null;

                            if (empty($unique_id)) {
                                $unique_id = aiosnexus_generate_unique_id('custom_unique_id');
                            }

                            if (empty($post_meta->review_source)) {
                                continue;
                            }

                            $review_source = $post_meta->review_source;
                            $content_fixed = $item->post_content ?? '';
                            $generated_ids[$review_source] = $unique_id;

                            $post_data = [
                                'post_type'    => sanitize_key($item->post_type),
                                'post_title'   => sanitize_text_field($item->post_title),
                                'post_content' => wp_kses_post($content_fixed),
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                                'meta_input'   => [
                                    'review_source'   => sanitize_text_field($review_source),
                                    'custom_unique_id' => sanitize_text_field($unique_id),
                                ],
                            ];

                            $insert_post = wp_insert_post($post_data);

                            if (is_wp_error($insert_post)) {
                                continue;
                            }
                        }

                        $response_data['status']  = 'success';
                        $response_data['message'] = 'Testimonials generated successfully';
                    }

                    if ($key === 'aios-testimonials') {
                        foreach ($content as $value) {

                            if ($value->post_type === 'aios-testimonials') {
                                $aios_client_info = get_option('aiis_ci');
                                $contentData = str_replace("ai_client_name", $aios_client_info['name'] ?? '', $value->post_content);
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

                            if (isset($post_meta->aios_testimonials_video_placeholder) && $insert_post) {
                                $extension  = !empty($post_meta->extension) ? $post_meta->extension . '/' : '';
                                $image_url  = $sPath . '/' . $extension . 'images/' . $post_meta->aios_testimonials_video_placeholder;
                                $existing   = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_attachment_by_source_url($image_url);
                                $image_data = $existing > 0 ? $existing : media_sideload_image($image_url, $insert_post, '', 'id');
                                update_post_meta($insert_post, 'aios_testimonials_video_placeholder', $image_data);
                            }

                        }

                        $response_data['status'] = 'success';
                        $response_data['message'] = 'Testimonials generated successfully';

                    }
                }
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_testimonials', true);
                update_option('aios_auto_population_testimonials_date', $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Testimonials already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new Testimonials();
