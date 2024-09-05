<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class ABOUT_CONTACT_GENERATE {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        
        register_rest_route('aios-populate/v1', '/about-contact', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_generate_about_contact'),
        ));
    }

    public function aios_populate_generate_about_contact($data) {
        
        $dateComplete = get_option('aios_auto_population_about_contact_generate_date', $data['date']);
        update_option('aios_auto_population_about_contact_generate_date',  $dateComplete);


        $generateAboutContact = get_option('aios_auto_population_about_contact_generate', false);

        $url =  get_stylesheet_directory_uri() .'/config.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));

        $data =  json_decode($response['body']);



        if($generateAboutContact != true){


            $config = $data->config;
            $productType = $config[0]->product_type;

            $image = $data->about_contact[0]->image;

            $extension = !empty($image->extension) ? '' . $image->extension . '/' : '';
            $image_url = get_stylesheet_directory_uri() . '/' . $extension . 'images/' . $image->image_name;

            $background_image_url = get_stylesheet_directory_uri() . '/' . $extension . 'images/' . $image->background;

            $aios_client_info = get_option('aiis_ci');

            // About
            $about =  $data->about_contact[0]->about;
            $about_options = get_option('about_options');


            // for profile photo
            $agentPhoto = media_sideload_image($image_url, $about_options['page_id'], '', 'id');

            $about_options['agent_team_photo'] = $agentPhoto;

            $aios_client_info['photo'] = wp_get_attachment_image_url($agentPhoto, 'full');

            update_option('aiis_ci', $aios_client_info);


            foreach($about as $key=>$content){

                
                if($key === 'theme'){
                    $about_options[$key] = $productType .'-'. $content;
                    
                }else{
                    $about_options[$key] = $content;
                }

            }
            update_option('about-theme', $productType .'-'. $about->theme );


            if (function_exists('autoPopulateCustomPages')) {
                autoPopulateCustomPages(
                    'about',
                    $about->theme,
                    true
                );
            }

            update_option('about_options', $about_options);

            // Contact 
            $contact =  $data->about_contact[0]->contact;
            $contact_options = get_option('contact_options');

            if($contact->theme !== "element"){
                $contact_options['agent_team_photo'] = $agentPhoto;
            }else{
                $backgroundImage = media_sideload_image($background_image_url, $contact_options['page_id'], '', 'id');
                $contact_options['agent_team_photo'] = $backgroundImage;
            }
            

            foreach ($contact as $key => $content) {

                if ($key === 'theme') {
                    $contact_options[$key] = $productType . '-' . $content;
                } else {

                    $finalKey = $key === 'address_display' ? 'address-display' : $key;
                    $contact_options[$finalKey] = $content;
                }
            }
            update_option('contact-theme', $productType . '-' . $contact->theme);

            if (function_exists('autoPopulateCustomPages')) {
                autoPopulateCustomPages(
                    'contact',
                    $contact->theme,
                    true
                );
            }

            update_option('contact_options', $contact_options);
            
            $response = array(
                'success' => true, 
                'message' => 'About and Contact Successfully Generated', 
                'date' => $dateComplete
            );

        }else{
    
            $response = array(
                'success' => false, 
                'message' => 'About and Contact Successfully Generated', 
                'date' => $dateComplete
            );
        }

        return rest_ensure_response($response);
    }
}
new ABOUT_CONTACT_GENERATE();