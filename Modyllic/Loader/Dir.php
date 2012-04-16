<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Loader_Dir {
    static function load( $dir, $schema ) {
        $filelist = glob("$dir/*",GLOB_NOSORT);
        $matches = array();
        foreach ($filelist as $file) {
            if ( substr($file,-4) == ".sql" or is_dir($file) ) {
                $matches[] = $file;
            }
        }
        natsort($matches);
        Modyllic_Loader::load( $matches, $schema );
    }
}
