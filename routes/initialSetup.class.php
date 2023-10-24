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
        

        $dateComplete = get_option('initial_setup_pages', $data['date']);

        $currentDateTime = date('m/d/Y, g:i:s A');
        update_option('initial_setup_pages',  $data['date']);


        $initialSetupPages = get_option( 'initial_setup_pages' );

        if (!$widgets_generated) {
            // Initial setup required pages
            $initial_required = AIOS_INITIAL_SETUP_DIR . DIRECTORY_SEPARATOR . 'backward-compatibility' . DIRECTORY_SEPARATOR . 'generate-default-pages.php';

            if (file_exists($initial_required) && include_once $initial_required) {
                $ids = [0, 1, 2, 3, 4, 5]; // Default IDs

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