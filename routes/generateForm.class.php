<?php

namespace AIOS\AUTOPOPULATE\Routes;

class Form
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/form', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_form'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_form($data)
    {

        /// Repopulation
        if ($data['repopulate']) {
            $formIds = get_option('aios_auto_population_form_id');
            foreach ($formIds as $id) {
                wp_delete_post($id);
            }
            delete_option('aios_auto_population_form');
            delete_option('aios_auto_population_form_date');
        }


        $dateComplete = get_option('aios_auto_population_form_date', $data['date']);
        $form_generated = get_option('aios_auto_population_form', false);
        $response_data = [];
        $response_data['date'] = $dateComplete;

        if (!$form_generated) {

            $active_theme = get_option('template');
            $sPath = ( $active_theme === 'aios-starter-theme' )
                ? get_stylesheet_directory_uri()
                : get_template_directory_uri();

            $json_data = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('config.json');

            $contact_form_id_arr = [];
            if ( ! $json_data ) {
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {

                $formsData = $json_data->contact_form[0];
                $cf7_current_post_type = 'wpcf7_contact_form';
                foreach ($formsData as $form) {

                    $form = $form[0];

                    $data_to_add = [
                        'post_title'    => $form->title,
                        'post_content'  => $form->content,
                        'post_type'		=> $cf7_current_post_type,
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                    ];

                    //Insert the post into the database
                    $contact_form_id = wp_insert_post($data_to_add);
                    $contact_form_id_arr[] = $contact_form_id;
                    if (!empty($contact_form_id)) {
                        update_post_meta($contact_form_id, '_messages', (array) $form->message);
                        update_post_meta($contact_form_id, '_mail', (array) $form->mail);
                        update_post_meta($contact_form_id, '_form', $form->form);

                        $count_cf7++;
                    }

                }
            }

            // Set the option to indicate that pages have been generated
            update_option('aios_auto_population_form_id', $contact_form_id_arr);
            update_option('aios_auto_population_form', true);
            update_option('aios_auto_population_form_date', $dateComplete);


            $response_data['status'] = 'success';
            $response_data['message'] = 'Form generated successfully';


        } else {
            $response_data['status'] = 'success';
            $response_data['message'] = 'Form already generated';
        }

        return rest_ensure_response($response_data);
    }
}

new Form();
