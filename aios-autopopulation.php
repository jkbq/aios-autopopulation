<?php

/**
 * Plugin Name: AIOS Auto Population
 * Description: Autopopulate Agentpro and AIX Theme
 * Version: 2.1.1
 * Author: Agent Image
 * Author URI: https://www.agentimage.com/
 * License: Proprietary
 */

namespace AIOS\AUTOPOPULATE;

define('AIOS_AUTOPOPULATE_URL', plugin_dir_url(__FILE__));
define('AIOS_AUTOPOPULATE_DIR', realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR);
define('AIOS_AUTOPOPULATE_JSON', plugin_dir_url(__FILE__) . 'routes/json/');
define('AIOS_AUTOPOPULATE_RESOURCES', AIOS_AUTOPOPULATE_URL . 'resources/');
define('AIOS_AUTOPOPULATE_VIEWS', AIOS_AUTOPOPULATE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);

require 'FileLoader.php';

$fileLoader = new FileLoader();

// Load Core
$fileLoader->load_files([

    'app' . DIRECTORY_SEPARATOR . 'App',

    // Config
    'config' . DIRECTORY_SEPARATOR . 'Config',

    // helpers
    'helpers' . DIRECTORY_SEPARATOR . 'Common',

    //controllers
    'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'AdminController',
    'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'FrontendController',

    //routes
    'routes' . DIRECTORY_SEPARATOR . 'about-contact.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-agents.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-buyers.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-communities.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-financing.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-listings.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-page.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-post.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-sellers.class',
    'routes' . DIRECTORY_SEPARATOR . 'generate-testimonials.class',
    'routes' . DIRECTORY_SEPARATOR . 'generateForm.class',
    'routes' . DIRECTORY_SEPARATOR . 'generateMenu.class',
    'routes' . DIRECTORY_SEPARATOR . 'generateRoadmaps.class',
    'routes' . DIRECTORY_SEPARATOR . 'generateSlider.class',
    'routes' . DIRECTORY_SEPARATOR . 'generateWidgets.class',
    'routes' . DIRECTORY_SEPARATOR . 'initialSetup.class',
    'routes' . DIRECTORY_SEPARATOR . 'regeneratePage.class',
    'routes' . DIRECTORY_SEPARATOR . 'settings.class',
    'routes' . DIRECTORY_SEPARATOR . 'deactivate.class',
]);

new App\App(__FILE__);
