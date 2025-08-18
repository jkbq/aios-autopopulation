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
            'methods'   => 'POST',
            'callback'  => [$this, 'aios_populate_testimonials'],
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


            $sPath = get_template_directory_uri();


            if ($active_theme  === 'aios-starter-theme') {
                $sPath = get_stylesheet_directory_uri();
            }
            $url =  $sPath . '/contents.json';

            $response = wp_remote_get($url, [
                'timeout' => 45,
                'blocking' => true,
                'cookies' => [],
            ]);

            if (is_wp_error($response)) {
                error_log(print_r($response->get_error_message(), true));
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {
                $contents = json_decode($response['body']);
                foreach ($contents as $key => $content) {

                    if ($key === 'aios-testimonials') {
                        foreach ($content as $value) {

                            $contentData = '';
                            if ($value->post_type === 'aios-testimonials') {
                                $aios_client_info = get_option('aiis_ci');
                                $contentData = str_replace("ai_client_name", $aios_client_info[ 'name' ], $value->post_content);
                            } else {
                                $contentData = $value->post_content;
                            }

                            $post_data = [
                                'post_type'    => $value->post_type,
                                'post_title'   => $value->post_title,
                                'post_content' => $contentData,
                                'post_status'  => 'publish',
                                'post_author'  => 1,
                            ];

                            $insert_post = wp_insert_post($post_data);

                            $post_meta = $value->meta_input[0];

                            update_post_meta($insert_post, 'aios_testimonials_video_url', $post_meta->aios_testimonials_video_url);
                            update_post_meta($insert_post, 'aios_testimonials_video_type', $post_meta->aios_testimonials_video_type);

                            if (isset($post_meta->aios_testimonials_video_placeholder)) {

                                $extension = !empty($post_meta->extension) ? '' . $post_meta->extension . '/' : '';
                                $image_url = $sPath . '/' . $extension . 'images/' . $post_meta->aios_testimonials_video_placeholder;

                                $image_data = media_sideload_image($image_url, $insert_post, '', 'id');

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
