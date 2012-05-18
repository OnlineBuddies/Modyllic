<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Loader_Dir {
    // Static class only
    private function __construct() {}

    static function load( $dir, $schema ) {
        $filelist = array_merge(
            glob("$dir/*.sql",GLOB_NOSORT),
            glob("$dir/*",GLOB_NOSORT|GLOB_ONLYDIR) );
        natsort($filelist);
        Modyllic_Loader::load( $filelist, $schema );
    }
}
