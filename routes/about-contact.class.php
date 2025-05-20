<?php

namespace AIOS\AUTOPOPULATE\Routes;

use AIOS\AUTOPOPULATE\Helpers\Helpers;

class ABOUT_CONTACT_GENERATE
{
    public function __construct()
    {
        add_action( 'rest_api_init', [$this, 'register_endpoints'] );
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/about-contact', [
            'methods'   => 'POST',
            'callback'  => [ $this, 'aios_populate_generate_about_contact' ],
        ]);
    }

    public function aios_populate_generate_about_contact($data)
    {
        $dateComplete = get_option('aios_auto_population_about_contact_generate_date', $data['date']);
        update_option('aios_auto_population_about_contact_generate_date',  $dateComplete);

        $apiResponse = [
            'success' => false,
            'message' => 'About and Contact Failed to Generated',
            'date' => $dateComplete
        ];

        $generateAboutContact = get_option('aios_auto_population_about_contact_generate', false);


        
        $active_theme = get_option('template');


        $sPath = get_template_directory_uri();

    
        if ( $active_theme  === 'aios-starter-theme') {
            $sPath = get_stylesheet_directory_uri();
        }

        $url = $sPath . '/config.json';

        $response = wp_remote_get($url, [
            'timeout' => 45,
            'blocking' => true,
            'cookies' => []
        ]);

        $data =  json_decode($response['body']);

        if ($generateAboutContact != true) {
            $config = $data->config;
            $productType = $config[0]->product_type;

            $image = $data->about_contact[0]->image;

            $extension = !empty($image->extension) ? '' . $image->extension . '/' : '';
            $image_path = $sPath . '/' . $extension . 'images/';
            $image_url = $image_path . $image->image_name;

            $background_image_url = $sPath . '/' . $extension . 'images/' . $image->background;

            $aios_client_info = get_option('aiis_ci');

            if ($data->about_contact) {
                // About
                $about = $data->about_contact[0]->about;
                $generatedResponse = "";

                if (! isset($about->disabled)) {
                    $about_options = get_option('about_options');

                    // for profile photo
                    $agentPhoto = media_sideload_image($image_path . $image->image_name, $about_options['page_id'], '', 'id');
                    $about_options['agent_team_photo'] = $agentPhoto;
                    $aios_client_info['photo'] = wp_get_attachment_image_url($agentPhoto, 'full');
                    update_option('aiis_ci', $aios_client_info);

                    foreach ($about as $key => $content) {
                        if ($key === 'theme') {
                            $about_options[$key] = $productType . '-' . $content;
                        } else {
                            if ($key === "about_overlay_photo") {
                                $about_options[$key] = media_sideload_image($image_path . $about->about_overlay_photo, $about_options['page_id'], '', 'id');;
                            } else {
                                $about_options[$key] = $content;
                            }
                        }
                    }

                    update_option('about-theme', $productType . '-' . $about->theme);

                    if (function_exists('autoPopulateCustomPages')) {
                        autoPopulateCustomPages(
                            'about',
                            $about->theme,
                            true
                        );
                    }

                    update_option('about_options', $about_options);

                    $generatedResponse = "About";
                }

                // Contact 
                $contact = $data->about_contact[0]->contact;

                if (! isset($contact->disabled)) {
                    $contact_options = get_option('contact_options');
                    
                    if (isset($contact->agent_team_photo)) {
                        $contactFormPhotoSrc = $sPath . '/' . $image->extension . '/images/' . $contact->agent_team_photo;
                        $contactFormPhoto = media_sideload_image($contactFormPhotoSrc, $contact_options['page_id'], '', 'id');
                        $contact_options['agent_team_photo'] = $contactFormPhoto;
                    } else {
                        if ($contact->theme !== "element") {
                            $contact_options['agent_team_photo'] = $agentPhoto;
                        } else {
                            $backgroundImage = media_sideload_image($background_image_url, $contact_options['page_id'], '', 'id');
                            $contact_options['agent_team_photo'] = $backgroundImage;
                        }
                    }

                    foreach ($contact as $key => $content) {
                        if ($key === 'theme') {
                            $contact_options[$key] = $productType . '-' . $content;
                        } else {
                            if ($key !== 'agent_team_photo') {
                                $finalKey = $key === 'address_display' ? 'address-display' : $key;
                                $contact_options[$finalKey] = $content;        
                            }         
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

                    $generatedResponse = $generatedResponse . (! empty($generatedResponse) ? " and" : "") . " Contact";
                }

                $apiResponse['success'] = true;
                $apiResponse['message'] = empty($generatedResponse) ? "No pages are generated" : "$generatedResponse successfully generated";

                update_option('aios_auto_population_about_contact_generate', true);
            }
        }

        return rest_ensure_response($apiResponse);
    }
}
new ABOUT_CONTACT_GENERATE();