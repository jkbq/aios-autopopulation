<?php

namespace AIOS\AUTOPOPULATE\Routes;

class AuthorPopulate
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/author-populate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_author_populate'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_author_populate($data)
    {
        $is_repopulate = ! empty($data['repopulate']);

        if ($is_repopulate) {
            \AIOS\AUTOPOPULATE\Helpers\Helpers::prepare_canned_section_for_repopulate('author');
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        }

        $dateComplete = get_option('aios_auto_population_author_date', $data['date']);

        $authors_generated = get_option('aios_auto_population_author', false);

        $response_data = [];

        $response_data['date'] = $dateComplete;

        if (! $authors_generated) {

            $active_theme = get_option('template');
            $sPath = ( $active_theme === 'aios-starter-theme' )
                ? get_stylesheet_directory_uri()
                : get_template_directory_uri();

            $contents = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('contents.json');

            if ( ! $contents ) {
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {

                $generated_ids = [];

                foreach ($contents as $key => $content) {

                    if ($key === 'author') {
                        foreach ($content as $value) {

                            $raw_meta = json_decode(json_encode($value->meta_input[0] ?? []), true);

                            $post_data = [
                                'post_type'   => 'aios_author',
                                'post_title'  => sanitize_text_field($value->post_title),
                                'post_status' => 'publish',
                                'meta_input'  => [
                                    'first_name'  => sanitize_text_field($raw_meta['first_name'] ?? ''),
                                    'last_name'   => sanitize_text_field($raw_meta['last_name'] ?? ''),
                                ],
                            ];

                            $insert_post = wp_insert_post($post_data);

                            if ($insert_post) {
                                $generated_ids[] = (int) $insert_post;

                                $image_id = 0;

                                if (isset($value->featured_image)) {
                                    $extension = !empty($value->extension) ? $value->extension . '/' : '';
                                    $image_url = $sPath . '/' . $extension . 'images/' . $value->featured_image;

                                    $existing_id = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_attachment_by_source_url($image_url);
                                    $image_data  = $existing_id > 0 ? $existing_id : media_sideload_image($image_url, $insert_post, '', 'id');

                                    if (is_wp_error($image_data)) {
                                        error_log('Error sideloading author photo: ' . $image_data->get_error_message());
                                    } else {
                                        $image_id = (int) $image_data;
                                        set_post_thumbnail($insert_post, $image_id);
                                    }
                                }

                                update_post_meta($insert_post, '_aios_author_details', [
                                    'author_photo_id' => $image_id,
                                    'description'      => wp_kses_post($raw_meta['description'] ?? ''),
                                    'first_name'       => sanitize_text_field($raw_meta['first_name'] ?? ''),
                                    'last_name'        => sanitize_text_field($raw_meta['last_name'] ?? ''),
                                    'facebook'         => esc_url_raw($raw_meta['facebook'] ?? ''),
                                    'twitter'          => esc_url_raw($raw_meta['twitter'] ?? ''),
                                    'instagram'        => esc_url_raw($raw_meta['instagram'] ?? ''),
                                    'youtube'          => esc_url_raw($raw_meta['youtube'] ?? ''),
                                    'linkedin'         => esc_url_raw($raw_meta['linkedin'] ?? ''),
                                    'tiktok'           => esc_url_raw($raw_meta['tiktok'] ?? ''),
                                    'pinterest'        => esc_url_raw($raw_meta['pinterest'] ?? ''),
                                    'rss'              => esc_url_raw($raw_meta['rss'] ?? ''),
                                ]);

                                \AIOS\AUTOPOPULATE\Helpers\Helpers::mark_canned_content_baseline( (int) $insert_post );

                                $response_data['status'] = 'success';
                                $response_data['message'] = 'Author generated successfully';

                            } else {
                                $response_data['status'] = 'error';
                                $response_data['message'] = 'Error inserting author';
                            }

                        }
                    } elseif (! isset($response_data['status'])) {
                        $response_data['status'] = 'success';
                        $response_data['message'] = 'Author generated successfully';
                    }
                }

                \AIOS\AUTOPOPULATE\Helpers\Helpers::store_canned_content_ids(
                    'aios_auto_population_author_ids',
                    $generated_ids,
                    $is_repopulate
                );

                update_option('aios_auto_population_author', true);
                update_option('aios_auto_population_author_date', $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Author already generated';
        }

        return rest_ensure_response($response_data);
    }
}
new AuthorPopulate();
