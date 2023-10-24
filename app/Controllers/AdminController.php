<?php

namespace AIOS\AUTOPOPULATE\App\Controllers;

class autopopulateAdmin
{

  /**
   * Admin constructor.
   */
  public function __construct()
  {
    // add_action('admin_menu', [$this, 'page']);
    add_action('admin_enqueue_scripts', [$this, 'assets']);
  }

  /**
   * Enqueue Assets to specific page
   */
  public function assets()
  {

    if (strpos(get_current_screen()->id, AIOS_AUTOPOPULATE_SLUG) !== false) {
      wp_enqueue_media();

      wp_enqueue_style('aios-sweetalert2-style', 'https://resources.agentimage.com/admin/css/swal.css');
      wp_enqueue_script('aios-sweetalert2-script', 'https://resources.agentimage.com/admin/js/sweetalert2.min.js');
      wp_enqueue_style(AIOS_AUTOPOPULATE_SLUG, AIOS_WEBPACKBASIC_RESOURCES . 'css/app.min.css', [], time());
      wp_enqueue_script(AIOS_AUTOPOPULATE_SLUG, AIOS_WEBPACKBASIC_RESOURCES . 'js/app.min.js', [], time(), true);
      wp_localize_script(AIOS_AUTOPOPULATE_SLUG, 'data', [
        'nonce' => wp_create_nonce('wp_rest'),
        'baseUrl' => get_home_url()
      ]);
    }
  }

  /**
   * Register admin page
   */
  public function page()
  {
    add_menu_page(
      AIOS_WEBPACKBASIC_NAME,
      AIOS_WEBPACKBASIC_NAME,
      'manage_options',
      AIOS_AUTOPOPULATE_SLUG,
      [$this, 'render'],
      'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE1LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHdpZHRoPSI0MzkuNzQ2cHgiIGhlaWdodD0iNDA4LjU2NnB4IiB2aWV3Qm94PSIwIDAgNDM5Ljc0NiA0MDguNTY2IiBzdHlsZT0iZmlsbDojODI4NzhjIg0KCSB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxwYXRoIGQ9Ik00MzkuNzQ2LDM5MS42Nzl2MTYuODg3SDI5Ny44MnYtMTYuODg3aDM0LjgyM2MzLjk4Mi0wLjE0OCwxNy42NzItMi4xODYsOS45OTEtMjMuNzc2bC0yOS4wNjQtNjguNzg1bDI1LjMzMi02My42ODQNCglsNTcuNjgsMTMzLjA1OGM4Ljg1NiwyMC4zNzUsMTguNzIxLDIyLjkzMywyMi4wMzEsMjMuMTg4SDQzOS43NDZ6IE0yMDMuMjUyLDcxLjMzM0wyMDMuMjUyLDcxLjMzM2w0LjExOS05LjAyOWgtMjQuNjI4DQoJTDQ3Ljk5LDM2OC40MDdsMC0wLjAwMmMtMTEuNzcyLDIwLjc4MS0yMS4yMDgsMjMuMDc1LTIzLjk1NSwyMy4yNzRIMHYxNi44ODdoNTQuMjM4di0wLjI0NWwwLjU1NywwLjI0NUwyMDMuMjUyLDcxLjMzM3oNCgkgTTQwNS44MDcsMy4wODhjLTIuOTIyLDIuNzYxLTEwLjIyOSw1LjUyMS0yNS44MTgsOS4yNTdjLTE1LjU5LDMuNzM1LTIzLjcwOSwxNi41NjMtMjMuNzA5LDE2LjU2Mw0KCWMzLjEyNy0yLjc1NywxNC4yODktNS42NDYsMjcuMDc4LTguMzI1QzM5Ni4xNDUsMTcuOTAzLDQwNS44MDcsMy4wODgsNDA1LjgwNywzLjA4OHogTTM3My4yMDcsMTEzLjI2NQ0KCWMwLDAsMTMuOTIyLTM2LjgzNSwzMS45NDUtMzcuODY2aDExLjA0N1Y2Mi4zMDVoLTU3LjYxMWMzLjI0Mi04LjE0MSwxMi45NTUtOS42OTIsMTIuOTU1LTkuNjkyDQoJYzIxLjkyMi00LjE0MSwzOC4yNDItMTEuMjA1LDM4LjI0Mi0xMS4yMDVjMjkuNzE3LTE3LjUzOCw0LjYyOS00MS40MDksNC42MjktNDEuNDA5YzYuODE4LDIyLjE2Ni04LjUyOCwyMS42NzktMzkuOTQ5LDMxLjE3OQ0KCWMtMzEuNDIyLDkuNS0zMC45MzQsMzEuMTI2LTMwLjkzNCwzMS4xMjZsMC4wMDEsMC4wMDFoLTM0LjUwN3YxMy4wOTRoMjMuNzg1YzIuNzE1LDAuMDM0LDI3LjU5OCwxLjE3MSwxNi4xMzUsMzEuODA5DQoJbC0yOS45NzUsNzYuMjU5TDI1My4xNjIsMzIuMTU0di0wLjAwMUgyMzguMDZMNzkuMzI3LDM5Mi4yMTZsLTcuNDIzLDE2LjM1aDkuNjE1aDM2LjE0NWg0Ny42NDZ2LTE2Ljg4N2gtMzcuMw0KCWMtMTIuODI2LTIuMDg2LTExLjkwNy0xMi44ODMtMTAuMTQtMTkuMzAxbDExMy4zNy0yNjkuNTg3bDAuMDA4LTAuMDI2bDYxLjM3NCwxNDYuMTc2bDAuMzMyLDAuNzE3bC02Mi4yMDIsMTU4LjI1M2gyNC4zNTgNCglsMTE4LjA2Ny0yOTQuNjUyTDM3My4yMDcsMTEzLjI2NXoiLz4NCjwvc3ZnPg0K',
      3
    );
  }

  /**
   * Render Page
   */
  public function render()
  {
    include_once AIOS_WEBPACKBASIC_VIEWS . 'index.php';
  }
}

new autopopulateAdmin();