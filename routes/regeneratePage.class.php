<?php 

namespace AIOS\AUTOPOPULATE\Routes;
use AIOS\AUTOPOPULATE\Helpers\Helpers;

class RegenerateContents {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        register_rest_route('aios-populate/v1', '/regeneratecontents', array(
            'methods'   => 'POST',
            'callback'  => array($this, 'aios_repopulate_page'),
        ));
    }

    public function aios_repopulate_page() {

        $url =  get_stylesheet_directory_uri() .'/config.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));

        $config =  json_decode($response['body']);
        $beforeTheme = get_option('aios_autopopulation_theme');
        $active_theme = get_option('template');
        $active_child_theme = get_option('stylesheet');
        $active_theme = $active_theme === 'aios-starter-theme' ?  $active_child_theme : $active_theme;

        // Default Libraries
        $libraries = $config->config[0]->libraries;

        
        $config = $data->config[0]->site_info;
        $post_title_option = get_option('aios-metaboxes-custom-title-post-types');
        $taxonomy_title_option = get_option('aios-metaboxes-custom-title-taxonomies');
        if (isset($client_info->banner_title_inside)){
    
            $taxonomy_title_option['title']['asiowpfiller'] = 'asiowpfiller';
            $taxonomy_title_option['title']['category'] = 'category';
            $taxonomy_title_option['title']['community-group'] = 'community-group';
            $taxonomy_title_option['title']['property-features'] = 'property-features';
            $taxonomy_title_option['title']['property-types'] = 'property-types';
            $taxonomy_title_option['title']['property-statuses'] = 'property-statuses';
            $taxonomy_title_option['title']['property-states'] = 'property-states';

            $post_title_option['title']['post'] = 'post';
            $post_title_option['title']['page'] = 'page';
            $post_title_option['title']['aios-neighborhood'] = 'aios-neighborhood';
            $post_title_option['title']['aios-agents'] = 'aios-agents';
            $post_title_option['title']['aios-communities'] = 'aios-communities';
            $post_title_option['title']['aios-concierge'] = 'aios-concierge';
            $post_title_option['title']['aios-rm-buyers'] = 'aios-rm-buyers';
            $post_title_option['title']['aios-rm-financing'] = 'aios-rm-financing';
            $post_title_option['title']['aios-rm-sellers'] = 'aios-rm-sellers';
            $post_title_option['title']['aios-testimonials'] = 'aios-testimonials';

            $inside_banner = get_option('aios-metaboxes-banner-title-layout');
            $inside_banner[1] = 1;
            update_option('aios-metaboxes-banner-title-layout', $inside_banner);
            update_option('aios-metaboxes-custom-title-post-types', $post_title_option);
            update_option('aios-metaboxes-custom-title-taxonomies', $taxonomy_title_option);

        }else{
            $taxonomy_title_option['title']['asiowpfiller'] = '';
            $taxonomy_title_option['title']['category'] = '';
            $taxonomy_title_option['title']['community-group'] = '';
            $taxonomy_title_option['title']['property-features'] = '';
            $taxonomy_title_option['title']['property-types'] = '';
            $taxonomy_title_option['title']['property-statuses'] = '';
            $taxonomy_title_option['title']['property-states'] = '';

            $post_title_option['title']['post'] = 'post';
            $post_title_option['title']['page'] = 'page';
            $post_title_option['title']['aios-neighborhood'] = '';
            $post_title_option['title']['aios-agents'] = '';
            $post_title_option['title']['aios-communities'] = '';
            $post_title_option['title']['aios-concierge'] = '';
            $post_title_option['title']['aios-rm-buyers'] = '';
            $post_title_option['title']['aios-rm-financing'] = '';
            $post_title_option['title']['aios-rm-sellers'] = '';
            $post_title_option['title']['aios-testimonials'] = '';

            $inside_banner = get_option('aios-metaboxes-banner-title-layout');
            $inside_banner[1] = '';
            update_option('aios-metaboxes-banner-title-layout', $inside_banner);
            update_option('aios-metaboxes-custom-title-post-types', $post_title_option);
            update_option('aios-metaboxes-custom-title-taxonomies', $taxonomy_title_option);
        }


        $communitiesConfig = $config->config[0]->plugins->aios_communities;
        // aios-communities
        update_option( 'communities-themes', ''.$communitiesConfig->theme.'-core' );


        $aios_enqueue_cdn = get_option( 'aios-enqueue-cdn' );

        foreach (  $libraries as $key=>$value){
            $aios_enqueue_cdn[$key] = $value;
        }

        update_option( 'aios-enqueue-cdn', $aios_enqueue_cdn );

        $aboutArgs = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'title' => 'About',
        );
        $aboutArrs = new \WP_Query($aboutArgs);
        $about =  $aboutArrs->posts[0];
        $contactArgs = array(
            'post_type' => 'page', 
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'title' => 'Contact',
        );
        $contactArrs = new \WP_Query($contactArgs);
        $contact =  $contactArrs->posts[0];

        if($beforeTheme != $active_theme ){
            
            $about_data = array(
                'ID'           => $about->ID,
                'post_title'   => 'About (Old) - '.$beforeTheme.'',
                'post_status'  => 'draft', // Set the status to draft
                'post_name' => 'about-old'
            );
            // Update the post in the database
            wp_update_post($about_data);
           
            $contact_data = array(
                'ID'           => $contact->ID,
                'post_title'   => 'Contact(Old) - '.$beforeTheme.'',
                'post_status'  => 'draft', // Set the status to draft
                'post_name' => 'contact-old'

            );
            // Update the post in the database
            wp_update_post($contact_data);

            update_option('aios_autopopulation_theme', $active_theme);

        }else{
            wp_delete_post($about->ID);
            wp_delete_post($contact->ID);
        }
        

        $url =  get_stylesheet_directory_uri() .'/contents.json';

        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'blocking' => true,
            'cookies' => array()
        ));
        
        $contents = json_decode($response['body']);

        foreach ($contents as $content) {
            foreach ($content as $value) {

                $post_data = array(
                    'post_type'    => $value->post_type,
                    'post_title'   => $value->post_title,
                    'post_content' => $value->post_content,
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                    'page_template' => $value->page_template
                );

                // Check post type and set category accordingly
                if ($value->post_type == 'post' && isset($value->post_category_id)) {
                    $post_data['post_category'] = array(get_cat_ID( 'Blog' ));
                }

                if($value->post_title == 'About' || $value->post_title == 'Contact'){ 
                    $insert_post = wp_insert_post($post_data);
                }



                if ($insert_post) {
                    // Post inserted successfully
                    $extension = !empty($value->extension) ? ''.$value->extension.'/' : '';
                    $image_url = get_stylesheet_directory_uri() . '/' . $extension . 'images/' . $value->featured_image;
                    $image_data = media_sideload_image($image_url, $insert_post, '', 'id');

                    // Set featured image using media_sideload_image
                    if (isset($value->featured_image)) {
                        

                        // Check if there is an error in sideloading the image
                        if (is_wp_error($image_data)) {
                            error_log('Error sideloading featured image: ' . $image_data->get_error_message());
                        } else {
                            // Get the attachment ID from the image data
                            $image_id = $image_data;

                            // Set the featured image
                            set_post_thumbnail($insert_post, $image_id);

                            // Debugging: Log the success
                            error_log('Featured image set for post ID: ' . $insert_post);
                        }


                        // Set thumbnail
                        if (!is_wp_error($image_id)) {
                            set_post_thumbnail($insert_post, $image_id);
                        }
                    }
                    // Debugging: Check if post is inserted successfully
                    $response_data['status'] = 'success';
                    $response_data['message'] = 'Pages generated successfully';

                   

                } else {
                    // Debugging: Check if there is an error during post insertion
                    $response_data['status'] = 'error';
                    $response_data['message'] = 'Error inserting post';
                }
            }
        }
                
        return rest_ensure_response($response_data);
    }
}
new RegenerateContents();