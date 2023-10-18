<?php 

namespace AiosAutoPopulate\Routes;

class Widgets {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/widgets', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_contents'),
        ));
    }

    public function aios_populate_contents($data) {
        
        $wigets_generated = get_option('wigets_generated', false);

        // this  block is to empty sidebar always before placing the new sidebar 
        // Get all registered sidebars
        $registered_sidebars = wp_get_sidebars_widgets();

        $sidebars_widgets = get_option('sidebars_widgets');

        if (!$wigets_generated) {
            foreach ($registered_sidebars as $sidebar_id => $widgets) {
                // Remove all widgets from the current sidebar
                $sidebars_widgets[$sidebar_id] = array();
                
                // Optionally, you can update other options to indicate that the sidebar is deregistered
                update_option($sidebar_id . '-deregistered', 'yes');
                update_option($sidebar_id . '-deregistered-timestamp', time()); // You can store a timestamp if needed
            }

            // Clear all widgets from inactive sidebars
            $inactive_sidebars = array_diff_key($sidebars_widgets, $registered_sidebars);

            foreach ($inactive_sidebars as $inactive_sidebar_id => $inactive_widgets) {
                $sidebars_widgets[$inactive_sidebar_id] = array();
            }


            $jsonData = AIOS_AUTOPOPULATE_JSON . 'config.json';

            $response = wp_remote_get($jsonData, array(
                'timeout' => 45,
                'blocking' => true,
                'cookies' => array()
            ));

            $data =  json_decode($response['body']);

            $widgets  = $data->widgets;

            // Loop through each widget in the array
            foreach ($widgets  as $widget_info) {
                $id = $widget_info->id;
                $type = $widget_info->type;
                $args = $widget_info->args;


                // Replace specific content in the $args array
                if (isset($args->pbcw_category)) {

                    // for blog
                    $args->pbcw_category = get_cat_ID( 'Blog' );
                }


                // Check if the sidebars_widgets option exists
                if (! sidebars_widgets ) {
                    $sidebars = array();
                }

                // Create the sidebar if it doesn't exist
                if (! isset($sidebars[$id])) {
                    $sidebars[$id] = array();
                }

                // Check for existing saved widgets
                $widget_opts = get_option("widget_$type");
                $still_empty = false;

                // Make sure that the widget array is really empty
                if (is_array($widget_opts)) {
                    $still_empty = count($widget_opts) === 1 && array_key_exists('_multiwidget', $widget_opts);
                }

                if ($widget_opts && ! $still_empty) {
                    // Get the next insert id
                    ksort($widget_opts);
                    end($widget_opts);
                    $insert_id = key($widget_opts);
                } else {
                    // None existing, start fresh
                    $widget_opts = array('_multiwidget' => 1);
                    $insert_id = 0;
                }

                // Add the widget data to the stack
                $widget_opts[++$insert_id] = $args;

                // Add the widget to the sidebar
                $sidebars[$id][] = "$type-$insert_id";

                // Update the options
                update_option('sidebars_widgets', $sidebars);
                update_option("widget_$type", $widget_opts);
            }
            
            // Set the option to indicate that pages have been generated
            update_option('wigets_generated', true);
            $response_data['status'] = 'success';
            $response_data['message'] = 'Form generated successfully';

        }else{
            $response_data['status'] = 'success';
            $response_data['message'] = 'Form already generated';
        }

        return rest_ensure_response($response_data);
    }
}
new widgets();