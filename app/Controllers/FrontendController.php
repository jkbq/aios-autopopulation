<?php

namespace AiosAutoPopulate\App\Controllers;

class FrontendController
{
    private $virtual_page_slug;

    public function __construct()
    {
        $this->virtual_page_slug = 'aios-installation';

        // Hook into switch_theme to handle theme activation
        add_action('after_switch_theme', [$this, 'handleThemeActivation']);

        // Hook into after_switch_theme for additional actions after theme switch
        add_action('after_switch_theme', [$this, 'handleAfterSwitchTheme']);

        add_action('query_vars', [$this, 'aios_install_set_query_var']);
        add_action('init', [$this, 'aios_install_custom_add_rewrite_rule']);


        add_action('wp_enqueue_scripts', [$this, 'aios_install_virtual_enqueue_scripts'], 15);

        add_filter('template_include', [$this, 'aios_install_virtual_include_template']);

    }

    /**
     * Handle theme activation logic
     */
    public function handleThemeActivation()
    {
        // Your previous redirection logic
        if (is_admin() && isset($_GET['activated']) && $current_slug != $this->virtual_page_slug) {
            update_option('aios_install_setup_visited', 'visited');
            $redirect = home_url() . '/' . $this->virtual_page_slug;
            $this->ap_redirect($redirect);
            exit;
        }

        // If theme activation happens on WP-CLI, this will redirect the users to the installation message page
        $aios_install_setup_visited = get_option('aios_install_setup_visited');
        if (!$aios_install_setup_visited) {
            update_option('aios_install_setup_visited', 'visited');
            $redirect = home_url() . '/' . $this->virtual_page_slug;
            $this->ap_redirect($redirect);
            exit;
        }
    }

    /**
     * Handle additional actions after theme switch
     */
    public function handleAfterSwitchTheme()
    {
        // Additional actions after theme switch (e.g., rewrite rules, permalinks)
        $this->flushRewriteRulesAndPermalinks();
    }

    /**
     * Your custom redirect function
     */
    private function ap_redirect($url)
    {
        wp_redirect($url);
        exit;
    }

    /**
     * Flush rewrite rules and update permalinks structure
     */
    private function flushRewriteRulesAndPermalinks()
    {
        $aios_install_flush_rewrite_rules = get_option('aios_install_flush_rewrite_rules');

        if ($aios_install_flush_rewrite_rules) {
            flush_rewrite_rules();

            // Add an option to indicate that rewrite rules have been flushed
            add_option('aios_install_setup_flush_rewrite', 'flushed');

            // Refresh permalinks with post name structure
            global $wp_rewrite;
            $wp_rewrite->set_permalink_structure('/%postname%/');
            $wp_rewrite->flush_rules(true);
        }
    }

    /**
     * Set query var for virtual page
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function aios_install_set_query_var($vars)
    {
        array_push($vars, $this->virtual_page_slug); // ref URL redirected to in add rewrite rule
        return $vars;
    }

    /**
     * Custom rewrite rules for virtual page
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function aios_install_custom_add_rewrite_rule()
    {
        add_rewrite_rule('^' . $this->virtual_page_slug, 'index.php?' . $this->virtual_page_slug . '=1', 'top');

        // Flush the rewrite rules, should be in a plugin activation hook, i.e., only run once...
        $aios_install_flush_rewrite_rules = get_option('aios_install_flush_rewrite_rules');

        if ($aios_install_flush_rewrite_rules) {
            flush_rewrite_rules();

            // Add an option to indicate that rewrite rules have been flushed
            add_option('aios_install_setup_flush_rewrite', 'flushed');

            // Refresh permalinks with post name structure
            global $wp_rewrite;
            $wp_rewrite->set_permalink_structure('/%postname%/');
            $wp_rewrite->flush_rules(true);
        }
    }

    /**
     * Custom rewrite rules for virtual page
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function aios_install_virtual_include_template($template)
    {
        if (get_query_var($this->virtual_page_slug)) {
            $template = AIOS_AUTOPOPULATE_DIR . '/resources/installation-page/template.php';
        }
        return $template;
    }

    /**
     * Enqueue Assets
     */
    public function aios_install_virtual_enqueue_scripts()
    {

        $current_slug = explode( '/', rtrim( $_SERVER[ 'REQUEST_URI' ], '\/' ) );
        if ( $current_slug[1] == $this->virtual_page_slug ) {
            wp_enqueue_style(AIOS_AUTOPOPULATE_SLUG, AIOS_AUTOPOPULATE_RESOURCES . 'css/frontend.min.css', [], time());
            wp_enqueue_script(AIOS_AUTOPOPULATE_SLUG, AIOS_AUTOPOPULATE_RESOURCES . 'js/frontend.min.js', [], time(), true);


            //dequeue

            wp_dequeue_script('aios-starter-theme-script');

        }
    }
}

new FrontendController();
