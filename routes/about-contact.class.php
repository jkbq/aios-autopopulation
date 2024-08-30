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


            // About
            $about =  $data->about_contact[0]->about;
            $about_options = get_option('about_options');


            $agentPhoto = media_sideload_image($image_url, $about_options['page_id'], '', 'id');


            $about_options['agent_team_photo'] = $agentPhoto;

            foreach($about as $key=>$content){

                if($key === 'theme'){
                    $about_options[$key] = $productType .'-'. $content;
                    
                }else{
                    $about_options[$key] = $content;
                }

            }
            update_option('about-theme', $productType .'-'. $about->theme );

            autoPopulateCustomPages(
                'about',
                $about->theme,
                true
            );

            update_option('about_options', $about_options);



            // Contact 
            $contact =  $data->about_contact[0]->contact;
            $contact_options = get_option('contact_options');


            $contact_options['agent_team_photo'] = $agentPhoto;


            foreach ($contact as $key => $content) {

                if ($key === 'theme') {
                    $contact_options[$key] = $productType . '-' . $content;
                } else {
                    $contact_options[$key] = $content;
                }
            }
            update_option('contact-theme', $productType . '-' . $contact->theme);

            autoPopulateCustomPages(
                'contact',
                $contact->theme,
                true
            );

            update_option('contact_options', $contact_options);
            
            $response = array(
                'success' => true, 
                'message' => 'About and Contact Successfully Generated', 
                'date' => $dateComplete
            );

        }else{
    
            $response = array(
                'success' => false, 
                'message' => 'Settings Already Generated', 
                'date' => $dateComplete
            );
        }

        return rest_ensure_response($response);
    }
}
new ABOUT_CONTACT_GENERATE();