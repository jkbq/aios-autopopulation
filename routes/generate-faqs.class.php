<?php

namespace AIOS\AUTOPOPULATE\Routes;

class FaqsPopulate
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/faqs-populate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_faqs_populate'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_faqs_populate($data)
    {
        $is_repopulate = ! empty($data['repopulate']);

        if ($is_repopulate) {
            \AIOS\AUTOPOPULATE\Helpers\Helpers::prepare_canned_section_for_repopulate('faqs');
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        }

        $dateComplete   = get_option('aios_auto_population_faqs_date', $data['date'] ?? '');
        $faqs_generated = get_option('aios_auto_population_faqs', false);

        $response_data = [];

        $response_data['date'] = $dateComplete;

        if (! $faqs_generated) {

            $contents = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('contents.json');

            if (! $contents || empty($contents->{'aios-faqs'})) {
                $response_data['status']  = 'error';
                $response_data['message'] = 'Error fetching JSON data';

                return rest_ensure_response($response_data);
            }

            $generated_ids = [];

            foreach ($contents->{'aios-faqs'} as $faq_set) {

                $entry_ids = [];

                foreach ($faq_set->entries ?? [] as $entry) {
                    $entry_post = wp_insert_post([
                        'post_type'    => 'aios-faqs-entries',
                        'post_title'   => sanitize_text_field($entry->question ?? ''),
                        'post_content' => wp_kses_post($entry->answer ?? ''),
                        'post_status'  => 'publish',
                        'post_author'  => 1,
                    ]);

                    if ($entry_post && ! is_wp_error($entry_post)) {
                        $entry_ids[] = (int) $entry_post;
                        \AIOS\AUTOPOPULATE\Helpers\Helpers::mark_canned_content_baseline((int) $entry_post);
                    }
                }

                $section_post = wp_insert_post([
                    'post_type'   => 'aios-faqs-section',
                    'post_title'  => sanitize_text_field($faq_set->post_title ?? ''),
                    'post_status' => 'publish',
                    'post_author' => 1,
                ]);

                if ($section_post && ! is_wp_error($section_post)) {
                    update_post_meta($section_post, 'aios_faq_entries', $entry_ids);
                    \AIOS\AUTOPOPULATE\Helpers\Helpers::mark_canned_content_baseline((int) $section_post);
                    $generated_ids[] = (int) $section_post;
                }
            }

            \AIOS\AUTOPOPULATE\Helpers\Helpers::store_canned_content_ids(
                'aios_auto_population_faqs_ids',
                $generated_ids,
                $is_repopulate
            );

            update_option('aios_auto_population_faqs', true);
            update_option('aios_auto_population_faqs_date', $dateComplete);

            $response_data['status']  = 'success';
            $response_data['message'] = 'FAQs generated successfully';
        } else {
            $response_data['status']  = 'success';
            $response_data['message'] = 'FAQs already generated';
        }

        return rest_ensure_response($response_data);
    }
}
new FaqsPopulate();
