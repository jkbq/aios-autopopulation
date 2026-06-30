<?php

namespace AIOS\AUTOPOPULATE\Routes;

class Communities
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/communities', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_communities'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    private static function get_metadata($value)
    {
        $meta_input = $value->meta_input[0] ?? [];
        if (!$meta_input) {
            return [];
        }

        // convert media gallery object to arrays
        if (isset($meta_input->aios_communities_media_gallery)) {
            $meta_input->aios_communities_media_gallery = json_decode(
                json_encode($meta_input->aios_communities_media_gallery),
                true
            );
        }

        return $meta_input;
    }

    public function aios_populate_communities($data)
    {

        $dateComplete = get_option('aios_auto_population_communities_date', $data['date']);

        $pages_generated = get_option('aios_auto_population_communities', false);

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

                    if ($key === 'aios-communities') {
                        foreach ($content as $value) {
                            $meta_input = self::get_metadata($value);
                            $post_data = [
                                'post_type'    => $value->post_type,
                                'post_title'   => $value->post_title,
                                'post_content' =>  $value->post_content,
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                                'meta_input'   => $meta_input,
                            ];

                            $insert_post = wp_insert_post($post_data);

                            if ($insert_post) {

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

                                // Debugging: Check if post is inserted successfully
                                error_log('Post inserted with ID: ' . $insert_post);
                                $response_data['status'] = 'success';
                                $response_data['message'] = 'Communities generated successfully';

                            } else {
                                $response_data['status'] = 'error';
                                $response_data['message'] = 'Error inserting post';
                            }
                        }
                    }
                }
                // Set the option to indicate that pages have been generated
                update_option('aios_auto_population_communities', true);
                update_option('aios_auto_population_communities_date', $dateComplete);
            }
        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Communities already generated';
        }
        return rest_ensure_response($response_data);
    }
}
new Communities();
