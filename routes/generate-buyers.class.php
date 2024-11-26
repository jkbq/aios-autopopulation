<?php 

namespace AIOS\AUTOPOPULATE\Routes;

class AiosRoadmapsBuyers {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/roadmaps-buyers', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_populate_aios_roadmaps_buyers'),
        ));
    }

    /**
     *  Insert Image from Post Generated
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function upload( $image_url, $post_id  ) {

        $url = $image_url;
 
        $tmp = download_url( $url );
        
        $file_array = array(
            'name' => basename( $url ),
            'tmp_name' => $tmp
        );
        
        /**
         * Check for download errors
         * if there are error unlink the temp file name
         */
        if ( is_wp_error( $tmp ) ) {
            @unlink( $file_array[ 'tmp_name' ] );
            return $tmp;
        }
        
        /**
         * now we can actually use media_handle_sideload
         * we pass it the file array of the file to handle
         * and the post id of the post to attach it to
         * $post_id can be set to '0' to not attach it to any particular post
         */
        $post_id = $post_id;
        
        $id = media_handle_sideload( $file_array, $post_id );
        
        /**
         * We don't want to pass something to $id
         * if there were upload errors.
         * So this checks for errors
         */
        if ( is_wp_error( $id ) ) {
            @unlink( $file_array['tmp_name'] );
            return $id;
        }
        
        /**
         * No we can get the url of the sideloaded file
         * $value now contains the file url in WordPress
         * $id is the attachment id
         */
        $value = wp_get_attachment_url( $id );


        set_post_thumbnail( $post_id, $id);
    }

    public function aios_populate_aios_roadmaps_buyers($data) {

    
        $dateComplete = get_option('aios_auto_population_roadmaps_buyers_date', $data['date']);

        $roadmapsStatus = get_option('aios_auto_population_roadmaps_buyers', false);

        $response_data = array();
        $response_data['date'] = $dateComplete;

        $buyers = get_option('aios-rm-buyers');
        $sellers = get_option('aios-rm-sellers');
        $financing = get_option('aios-rm-financing');

        if (empty($buyers)){

            $uploaders = new AiosRoadmapsBuyers();

            $image = AIOS_ROADMAPS_RESOURCES.'/images';
    
            $aios_roadmaps_settings = get_option('aios_roadmaps_settings');

            $date = date('Y-m-d H:i:s');
            // Get Sellers, Buyers, Financing json data
            $buyers_data        = AIOS_ROADMAPS_RESOURCES . '/json/aios-roadmaps-buyers-data.json';
            
            //Array for Default Pages for Sellers, Buyers, Financing
            $mainPages = ['Buyers', 'Sellers', 'Financing'];

            $pagesId = '';
            foreach($mainPages as $page){
                $pages = array(
                'post_title'    => $page,
                'post_type'      => 'page',
                'post_status'   => 'publish',
                'post_author'   => 1,
                );

                if($page == 'Buyers'){
                    $aios_roadmaps_settings['buyers_page'] = wp_insert_post( $pages );
                    update_option('aios_roadmaps_settings', $aios_roadmaps_settings);
                }
            }

            //Buyers 
            $buyers_response = wp_remote_get( $buyers_data, array(
                    'timeout' => 45,
                    'blocking' => true,
                    'body' => array( 'access' => 'true'),
                    'cookies' => array()
                )
            );

            $buyers_data_arr = json_decode( $buyers_response['body'] );
            
            $buyers_ids = [];
            if ( is_wp_error( $buyers_response ) ) {
                error_log( print_r ( $buyers_response->get_error_message(), true ) );
            } else {
                foreach($buyers_data_arr->posts as $posts ){
                    $data_args = array(
                        'post_type' => 'aios-rm-buyers',
                        'post_title' => $posts->post_title,
                        'post_date' => date('Y-m-d H:i:s', strtotime("".$date."-".$posts->post_time." seconds")),
                        'post_content' => $posts->post_content,
                        'post_status' => 'publish',
                        'post_author' => 1,
                        'meta_input'   => array(
                            'aios_roadmaps_icon_type' => $posts->icon_type,
                            'aios_roadmaps_font_image'   => $posts->font_image,
                            'aios_roadmaps_font_icon'   => $posts->font_icon
                        ),
                    );
                    $postsIDs = wp_insert_post( $data_args );
                    $uploaders->upload(''.$image.'/'.$posts->featured_image.'', $postsIDs);
                    $buyers_ids[] = $postsIDs;   
                } 
                update_option('aios-rm-buyers', $buyers_ids);
            }

            // Check if aios-roadmaps-ids has value
            $aios_roadmaps_status = get_option('aios-roadmaps');
            if($aios_roadmaps_status != 'freshly-installed'){
                // Set option upon activation
                update_option('aios-roadmaps', 'freshly-installed');
            }


            update_option('aios_auto_population_roadmaps_buyers', true);
            update_option('aios_auto_population_roadmaps_buyers_date', $dateComplete );


            $response_data['status'] = true;
            $response_data['message'] = 'Buyers generated successfully.';

        }else{
   
            $response_data['status'] = false;
            $response_data['message'] = 'Roadmaps already generated.';
        }

        return rest_ensure_response($response_data);
    }
}
new AiosRoadmapsBuyers();