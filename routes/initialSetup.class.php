<?php 


class InitialSetupPage {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/initial-setup-pages', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_default_settings'),
        ));
    }

    public function aios_populate_default_settings($data) {
        

        $dateComplete = get_option('aios_auto_population_default_pages_date', $data['date']);

        $initialSetupPages = get_option( 'aios_auto_population_default_pages', false );



        $active_theme = get_option('template');


        $sPath = get_template_directory_uri();

    
        if ( $active_theme  === 'aios-starter-theme') {
            $sPath = get_stylesheet_directory_uri();
        }

        $url =  $sPath .'/config.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));

        $data =  json_decode($response['body']);

        $client_info = $data->config[0]->site_info;

        if (!$initialSetupPages) {
            // Initial setup required pages
            $initial_required = AIOS_INITIAL_SETUP_DIR . DIRECTORY_SEPARATOR . 'backward-compatibility' . DIRECTORY_SEPARATOR . 'generate-default-pages.php';

            if (file_exists($initial_required) && include_once $initial_required) {
                $ids = [];
                if(isset($client_info->has_contact_form)){
                    $ids = [0, 1, 2, 3, 4, 5];
                }else{
                    $ids = [0, 1, 2, 4, 5];
                }
                $aios_initial_setup_generate_default_pages = new aios_initial_setup_generate_default_pages();
                $aios_initial_setup_generate_default_pages->generate_default_pages($ids);

                // Create your response data here (example: an array)
                $response_data = [
                    'message' => 'Default generated successfully',
                    'date' => $dateComplete,
                    // Add other response data here
                ];
            } else {
                // Handle the case where the file couldn't be included.
                // You can log an error, display a message, or take other appropriate actions.

                // Create an error response
                $response_data = [
                    'message' => 'Error including the file',
                    'date' => $dateComplete,
                    // Add other error response data here
                ];
            }

            update_option('aios_auto_population_default_pages', true);
            update_option('aios_auto_population_default_pages_date',  $dateComplete);


        } else {
            $response_data = [
                'message' => 'Default already generated successfully',
                'date' => $dateComplete,
                // Add other error response data here
            ];
        }

        return rest_ensure_response($response_data);
    }
}
new InitialSetupPage();