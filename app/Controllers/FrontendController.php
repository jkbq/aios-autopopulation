<?php

namespace AiosAutoPopulate\App\Controllers;

class FrontendController
{
  /**
   * Admin constructor.
   */

  private $virtual_page_slug;
  
  public function __construct()
  {


     $this->virtual_page_slug = 'aios-installation';
            
      $ap_setup = get_option( 'ap_setup' );
      $ap_setup_visited = get_option( 'ap_setup_visited' );
      
      $current_slug = explode( '/', rtrim( $_SERVER[ 'REQUEST_URI' ], '\/' ) );
      $current_slug = end( $current_slug );
      
      $redirect = home_url() . '/' . $this->virtual_page_slug;
      
      // Redirect after theme activation
      if ( is_admin() && isset( $_GET['activated'] ) && $current_slug != $this->virtual_page_slug ) {
          update_option( 'ap_setup_visited', 'visited' );
          header( "Location: " . $redirect );
          die;
      }
      
      // If theme activation happens on wp_cli, this will redirect the users to the installation message page
      if ( ! $ap_setup_visited ) {
          update_option( 'ap_setup_visited', 'visited' );
          
          header( "Location: " . $redirect );
          die;
      }
      
      add_action( 'query_vars', [ $this, 'ap_set_query_var' ] );
      add_action( 'init', [ $this, 'ap_custom_add_rewrite_rule' ] );
      // add_action( 'wp_enqueue_scripts', [ $this, 'ap_virtual_enqueue_scripts' ], 15 );

      add_filter( 'template_include', [ $this, 'ap_virtual_include_template' ] );

      if ( $current_slug == $this->virtual_page_slug ) {
          update_option( 'ap_setup', 'installed' );
          update_option( 'ap_setup_visited', 'visited' );
      }

  }

        
  /**
   * Set query var for virtual page
   *
   * @since 1.0.0
   *
   * @access public
   */
  public function ap_set_query_var( $vars ) {
      array_push( $vars, $this->virtual_page_slug ); // ref url redirected to in add rewrite rule
      return $vars;
  }
        
  /**
   * Custom rewrite rules for virtual page
   *
   * @since 1.0.0
   *
   * @access public
   */
  public function ap_custom_add_rewrite_rule() {
      add_rewrite_rule( '^' . $this->virtual_page_slug, 'index.php?' . $this->virtual_page_slug . '=1', 'top' );

      //flush the rewrite rules, should be in a plugin activation hook, i.e only run once...
      $ap_flush_rewrite_rules = get_option( 'ap_flush_rewrite_rules' );

      if ( $ap_flush_rewrite_rules ) {
          flush_rewrite_rules();

          add_option( 'ap_setup_flush_rewrite', 'flushed' );
      }
  }
        
  /**
   * Custom rewrite rules for virtual page
   *
   * @since 1.0.0
   *
   * @access public
   */
    public function ap_virtual_include_template( $template ) {
        if ( get_query_var( $this->virtual_page_slug ) ){
          $template = AIOS_AUTOPOPULATE_DIR . '\resources\installation-page/template.php';
        }
        return $template;
    }

  /**
   * Enqueue Assets
   */
  public function assets()
  {
    wp_enqueue_style(AIOS_AUTOPOPULATE_SLUG, AIOS_AUTOPOPULATE_RESOURCES . 'css/frontend.min.css', [], time());
    wp_enqueue_script(AIOS_AUTOPOPULATE_SLUG, AIOS_AUTOPOPULATE_RESOURCES . 'js/frontend.min.js', [], time(), true);
  }
}

new FrontendController();
