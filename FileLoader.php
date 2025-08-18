<?php

namespace AIOS\AUTOPOPULATE;

class FileLoader
{
    /**
     * Loads specified PHP files from the plugin includes directory.
     *
     * @param array $file_names The names of the files to be loaded in the includes directory.
     * @access public
     */
    public function load_files($file_names = [])
    {
        foreach ($file_names as $file_name) {
            if (file_exists($path = AIOS_AUTOPOPULATE_DIR . $file_name . '.php')) {
                require_once $path;
            }
        }
    }
}
