<?php
/**
 * Plugin Name: AIOS Auto Population
 * Description: Autopopulate Agentpro and AIX Theme
 * Version: 1.0.5
 * Author: Agent Image
 * Author URI: https://www.agentimage.com/
 * License: Proprietary
 */

namespace AIOS\AUTOPOPULATE;

define('AIOS_AUTOPOPULATE_URL', plugin_dir_url( __FILE__ ));
define('AIOS_AUTOPOPULATE_DIR', realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR);
define('AIOS_AUTOPOPULATE_JSON', plugin_dir_url( __FILE__ ) . 'routes/json/' );
define('AIOS_AUTOPOPULATE_RESOURCES', AIOS_AUTOPOPULATE_URL . 'resources/');
define('AIOS_AUTOPOPULATE_VIEWS', AIOS_AUTOPOPULATE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);



require 'FileLoader.php';

$fileLoader = new FileLoader();

// Load Core
$fileLoader->load_files(['app/App']);

new App\App(__FILE__);

// Load Files
$fileLoader->load_directory('helpers');
$fileLoader->load_directory('config');
$fileLoader->load_directory('app/Controllers');
$fileLoader->load_directory('routes');