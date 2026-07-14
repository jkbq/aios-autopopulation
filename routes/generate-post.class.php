<?php

namespace AIOS\AUTOPOPULATE\Routes;

class PostPopulate
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/post-populate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_post_populate'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_post_populate($data)
    {
        $is_repopulate = ! empty($data['repopulate']);

        if ($is_repopulate) {
            \AIOS\AUTOPOPULATE\Helpers\Helpers::prepare_canned_section_for_repopulate('post');
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        }

        $dateComplete = get_option('aios_auto_population_post_date', $data['date']);

        $pages_generated = get_option('aios_auto_population_post', false);

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

            $cid = wp_insert_term(
                'Blog',
                'category',
                [
                    'slug' => 'blog',
                    'description' => $contents->category[0]->description ?? '',
                ],
            );


                foreach ($contents as $key => $content) {

                    if ($key === 'post') {
                        foreach ($content as $value) {

                            $post_data = [
                                'post_type'    => sanitize_key($value->post_type),
                                'post_title'   => sanitize_text_field($value->post_title),
                                'post_content' => wp_kses_post($value->post_content),
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                            ];

                            // Check post type and set category accordingly
                            if ($value->post_type == 'post' && isset($value->post_category_id)) {
                                $post_data['post_category'] = [get_cat_ID('Blog')];
                            }

                            $insert_post = wp_insert_post($post_data);

                            if ($insert_post) {
                                $generated_ids[] = (int) $insert_post;

                                $extension = !empty($value->extension) ? $value->extension . '/' : '';
                                $image_url = $sPath . '/' . $extension . 'images/' . $value->featured_image;

                                $existing_id = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_attachment_by_source_url($image_url);
                                $image_data  = $existing_id > 0 ? $existing_id : media_sideload_image($image_url, $insert_post, '', 'id');

                                // Set featured image using media_sideload_image
                                if (isset($value->featured_image)) {


                                    // Check if there is an error in sideloading the image
                                    if (is_wp_error($image_data)) {
                                        error_log('Error sideloading featured image: ' . $image_data->get_error_message());
                                    } else {
                                        // Get the attachment ID from the image data
                                        $image_id = $image_data;

                                        // Set the featured image
                                        set_post_thumbnail($insert_post, $image_id);

                                        // Debugging: Log the success
                                        error_log('Featured image set for post ID: ' . $insert_post);
                                    }

                                    // Set thumbnail
                                    if (!is_wp_error($image_id)) {
                                        set_post_thumbnail($insert_post, $image_id);
                                    }
                                }

                                \AIOS\AUTOPOPULATE\Helpers\Helpers::mark_canned_content_baseline( (int) $insert_post );

                                // Debugging: Check if post is inserted successfully
                                error_log('Post inserted with ID: ' . $insert_post);
                                $response_data['status'] = 'success';
                                $response_data['message'] = 'Post generated successfully';

                            } else {
                                $response_data['status'] = 'error';
                                $response_data['message'] = 'Error inserting post';
                            }

                        }
                    } else {
                        $response_data['status'] = 'success';
                        $response_data['message'] = 'Post generated successfully';
                    }
                }
                \AIOS\AUTOPOPULATE\Helpers\Helpers::store_canned_content_ids(
                    'aios_auto_population_post_ids',
                    $generated_ids,
                    $is_repopulate
                );
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_post', true);
                update_option('aios_auto_population_post_date', $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Post already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new PostPopulate();
