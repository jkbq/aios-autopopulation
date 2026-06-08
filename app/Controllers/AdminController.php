<?php

namespace AIOS\AUTOPOPULATE\App\Controllers;

class autopopulateAdmin
{
    /**
     * Admin constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'page'], 11);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    /**
     * Enqueue Assets to specific page
     */
    public function assets($scripts)
    {


        $admin_page_id = get_current_screen()->id;
        $admin_page_contains = 'aios-autopopulation';

        if (strpos($admin_page_id, $admin_page_contains) === false) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style('aios-sweetalert2-style', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css');
        wp_enqueue_script('aios-sweetalert2-script', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js');

        if (!wp_style_is('aios-wpuikit-style', 'enqueued') || !wp_script_is('aios-wpuikit-script', 'enqueued')) {
            wp_enqueue_style('aios-wpuikit-style', 'https://resources.agentimage.com/wpuikit/v1/wpuikit.min.css', null, null);
            wp_enqueue_script('aios-wpuikit-script', 'https://resources.agentimage.com/wpuikit/v1/wpuikit.min.js', null, null);
        }


        wp_enqueue_style(AIOS_AUTOPOPULATE_URL, AIOS_AUTOPOPULATE_RESOURCES . 'css/app.min.css', [], time());
        wp_enqueue_script(AIOS_AUTOPOPULATE_URL, AIOS_AUTOPOPULATE_RESOURCES . 'js/app.min.js', [], time(), true);
        wp_localize_script(AIOS_AUTOPOPULATE_URL, 'data', [
            'nonce' => wp_create_nonce('wp_rest'),
            'baseUrl' => get_home_url(),
        ]);
    }

    /**
     * Register admin page
     */
    public function page()
    {

        add_submenu_page(
            'aios-all-in-one',
            'Autopopulation',
            'AIOS Auto Population',
            "manage_options",
            "aios-autopopulation",
            [$this, 'render'],
        );



    }

    /**
     * Render Page
     */
    public function render()
    {
        include_once AIOS_AUTOPOPULATE_VIEWS . 'index.php';
    }
}

new autopopulateAdmin();