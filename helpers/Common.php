<?php

namespace AIOS\AUTOPOPULATE\Helpers;

class Helpers {

  /**
   * @param $themes
   * @return 
   */
  public function agentpro_themes()
  {

    $themes = [
      "AgentPro Galaxy",
      "AgentPro Beacon",
      "AgentPro Panorama",
      "AgentPro Radiance",
      "AgentPro Purist",
      "AgentPro Endeavor",
      "AgentPro Legacy",
      "AgentPro Amante II",
      "AgentPro Element",
      "AgentPro Iconic",
      "AgentPro Vega",
      "AgentPro Maven",
      "AgentPro Metropolitan",
      "AgentPro Equinox",
      "AIX Royale",
      "AIX Quantum",
      "AIX Seneca",
      "AIX Hamilton",
    ];

    return $themes;
  }
  /**
   * @param $themes
   * @return 
   */
  public function api_status()
  {

    $apiName = 'aios_auto_population_';


    $apiStatus = [
        "Settings" => [
          "status" => get_option($apiName.'initial_setup_assets'),
          "date" => get_option($apiName.'initial_setup_assets_date')
        ],
        "Default Pages" => [
          "status" => get_option($apiName.'default_pages'),
          "date" => get_option($apiName.'default_pages_date')
        ],
        "Forms" => [
          "status" => get_option($apiName.'form'),
          "date" => get_option($apiName.'form_date')
        ],
        "Contents" => [
          "status" => get_option($apiName.'default_contents'),
          "date" => get_option($apiName.'default_contents_date')
        ],
        "Roadmaps" => [
          "status" => get_option($apiName.'roadmaps'),
          "date" => get_option($apiName.'roadmaps_date')
        ],
        "Slideshow" => [
          "status" => get_option($apiName.'slider'),
          "date" => get_option($apiName.'slider_date')
        ],
        "Menu" => [
          "status" => get_option($apiName.'menu'),
          "date" => get_option($apiName.'menu_date')
        ],
        "Widgets" => [
          "status" => get_option($apiName.'widgets'),
          "date" => get_option($apiName.'widgets_date')
        ],
    ];
    return $apiStatus;
  }

  /**
   * @param $themes
   * @return 
   */
  public function data($json)
  {

    $jsonData =  get_stylesheet_directory_uri() .'/'. $json;

    $response = wp_remote_get($jsonData, array(
        'timeout' => 45,
        'blocking' => true,
        'cookies' => array()
    ));

    return $response;
  }

}