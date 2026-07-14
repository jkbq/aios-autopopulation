<?php

namespace AIOS\AUTOPOPULATE\Routes;

class Agents
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/agents', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_agents'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_agents($data)
    {
        $is_repopulate = ! empty($data['repopulate']);

        if ($is_repopulate) {
            \AIOS\AUTOPOPULATE\Helpers\Helpers::prepare_canned_section_for_repopulate('agents');
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        }

        $dateComplete = get_option('aios_auto_population_agents_date', $data['date']);

        $pages_generated = get_option('aios_auto_population_agents', false);

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

                $generated_ids = [];

                foreach ($contents as $key => $content) {

                    if ($key === 'aios-agents') {
                        foreach ($content as $value) {

                            $raw_meta  = json_decode(json_encode($value->meta_input[0] ?? []), true);
                            $first     = sanitize_text_field($raw_meta['first_name'] ?? '');
                            $last      = sanitize_text_field($raw_meta['last_name'] ?? '');

                            $post_data = [
                                'post_type'    => sanitize_key($value->post_type),
                                'post_title'   => sanitize_text_field($value->post_title),
                                'post_content' => wp_kses_post($value->post_content),
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                                'meta_input'   => [
                                    'first_name' => $first,
                                    'last_name'  => $last,
                                    'full_name'  => trim("$first $last"),
                                    'position'   => sanitize_text_field($raw_meta['position'] ?? ''),
                                    'license'    => sanitize_text_field($raw_meta['license'] ?? ''),
                                    'email'      => sanitize_email($raw_meta['email_address'] ?? ''),
                                ],
                            ];

                            $insert_post = wp_insert_post($post_data);

                            if ($insert_post) {
                                $generated_ids[] = (int) $insert_post;

                                $extension = !empty($value->extension) ? $value->extension . '/' : '';
                                $image_url = $sPath . '/' . $extension . 'images/' . $value->featured_image;

                                $existing_id = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_attachment_by_source_url($image_url);
                                $image_data  = $existing_id > 0 ? $existing_id : media_sideload_image($image_url, $insert_post, '', 'id');

                                if (isset($value->featured_image)) {
                                    if (is_wp_error($image_data)) {
                                        error_log('Error sideloading featured image: ' . $image_data->get_error_message());
                                    } else {
                                        set_post_thumbnail($insert_post, $image_data);
                                    }
                                }

                                if ($value->post_type === 'aios-agents') {
                                    $raw_meta['agentimage_id'] = $image_data;
                                    update_post_meta($insert_post, '_agent_details', $raw_meta);

                                    if ( ! empty($raw_meta['featured']) ) {
                                        update_post_meta($insert_post, 'featured', $raw_meta['featured']);
                                    }
                                }

                                \AIOS\AUTOPOPULATE\Helpers\Helpers::mark_canned_content_baseline( (int) $insert_post );

                                $response_data['status'] = 'success';
                                $response_data['message'] = 'Post already generated';

                            } else {
                                $response_data['status'] = 'error';
                                $response_data['message'] = 'Error inserting post';
                            }

                        }
                    } else {
                        $response_data['status'] = 'success';
                        $response_data['message'] = 'Agents generated successfully';
                    }
                }
                \AIOS\AUTOPOPULATE\Helpers\Helpers::store_canned_content_ids(
                    'aios_auto_population_agents_ids',
                    $generated_ids,
                    $is_repopulate
                );
                update_option('aios_auto_population_agents', true);
                update_option('aios_auto_population_agents_date', $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Agents already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new Agents();
