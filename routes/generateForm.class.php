<?php 

namespace AIOS\AUTOPOPULATE\Routes;

class Form {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/form', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_form'),
        ));
    }

    public function aios_populate_form($data) {


        $currentDateTime = date('m/d/Y, g:i:s A');
        $dateComplete = get_option('form_generated_date_complete', $data['date']);
        $form_generated = get_option('form_generated', false);
        $response_data = array();
        $response_data['date'] = $dateComplete;
        
        if (!$form_generated) {
            $jsonData = get_stylesheet_directory_uri() . '/config.json';

            $response = wp_remote_get($jsonData, array(
                'timeout' => 45,
                'blocking' => true,
                'cookies' => array()
            ));

            if (is_wp_error($response)) {
                error_log(print_r($response->get_error_message(), true));
                $response_data['status'] = 'error';
                $response_data['message'] = 'Error fetching JSON data';
            } else {

                $data =  json_decode($response['body']);
                $formsData = $data->contact_form[0];
                $cf7_current_post_type = 'wpcf7_contact_form';
                foreach($formsData as $form){

                    $form = $form[0];

                    $data_to_add = array(
                        'post_title'    => $form->title,
                        'post_content'  => $form->content,
                        'post_type'		=> $cf7_current_post_type,	
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id()
                    );

                    //Insert the post into the database
                    $contact_form_id = wp_insert_post( $data_to_add );

                    if ( !empty( $contact_form_id ) ) {
                        update_post_meta($contact_form_id, '_messages',(array)$form->message); 
                        update_post_meta($contact_form_id, '_mail',(array)$form->mail); 
                        update_post_meta($contact_form_id, '_form', $form->form);
                        
                        $count_cf7++;
                    }

                }
            }

            // Set the option to indicate that pages have been generated
            update_option('form_generated', true);
            update_option('form_generated_date_complete', $currentDateTime );


            $response_data['status'] = 'success';
            $response_data['message'] = 'Form generated successfully';
           

        }else{
            $response_data['status'] = 'success';
            $response_data['message'] = 'Form already generated';
        }

        return rest_ensure_response($response_data);
    }
}

new Form();