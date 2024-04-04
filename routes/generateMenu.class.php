<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class Menu {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/menu', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_menu'),
        ));
    }

    public function aios_populate_menu($data) {

        

        if ($data['repopulate']){

            delete_option('aios_auto_population_menu_date');
            delete_option('aios_auto_population_menu');

            $menu_ids = get_option('aios_auto_population_menu_ids');

            foreach( $menu_ids as $ids){
                wp_delete_nav_menu($ids);
            }
        }

        $dateComplete = get_option('aios_auto_population_menu_date', $data['date']);
        $menuStatus         =  get_option('aios_auto_population_menu', false);
        


        $url =  get_stylesheet_directory_uri() .'/config.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));
        
        $data =  json_decode($response['body']);

        $menus = $data->menu[0];


        $response_data = array();
        $response_data['date'] = $dateComplete;
        
        $menuIds = [];
    
        foreach ($menus as $menu) {
            foreach ($menu as $menu_data) { 

                $location = $menu_data->location;
                $menu_name = $menu_data->menu_name;
                $nav_items = $menu_data->nav;

                // Check if the menu exists by location
                $menu_exists = wp_get_nav_menu_object($menu_name);

                if (!$menu_exists) {
                    if (isset($nav_items)) {
                        $menu_id = wp_create_nav_menu($menu_name); // Use the actual menu name
                        $locations = get_theme_mod('nav_menu_locations');
                        $locations[$location] = $menu_id;
                        set_theme_mod('nav_menu_locations', $locations);

                        $parent_id_arr = array(0);
                        $parent_id = 0;

                        foreach ($nav_items as $json_data) {
                            if ($json_data->post_type == 'custom-navigation') {
                                $parent_id = wp_update_nav_menu_item(
                                    $menu_id,
                                    0,
                                    array(
                                        'menu-item-title' => __($json_data->title),
                                        'menu-item-url' => home_url($json_data->url),
                                        'menu-item-status' => 'publish',
                                        'menu-item-parent-id' => $parent_id_arr[$json_data->parent],
                                        'menu-item-classes' => $json_data->class,
                                    )
                                );
                                $count_menu++;
                            }
                            // Set parent and child
                            $parent_id_arr[$json_data->id] = $parent_id;

                        }
                    }
                    

                    $menuIds[] = $menu_id;
                    
  
                    update_option('aios_auto_population_menu', true );
                    update_option('aios_auto_population_menu_date', $dateComplete );

                    $response_data['status'] = 'success';
                    $response_data['message'] = 'Menu generated successfully';


                }else{
                    $response_data['status'] = 'success';
                    $response_data['message'] = 'Menu generated successfully';
                }
            }
        }

        update_option('aios_auto_population_menu_ids', $menuIds);

        return rest_ensure_response($response_data);
    }
}
new Menu();