<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

// Enable assertions for our tests
assert_options( ASSERT_ACTIVE, 1 );
assert_options( ASSERT_BAIL, 1 );
assert_options( ASSERT_WARNING, 1 );

// Be maximally strict with errors and die on any warning or notice
function test_error_handler($code=0, $message="", $file="", $line=-1) {
    if ( error_reporting() & $code ) {
        die("$message in $file at line $line\n");
    }
}
set_error_handler('test_error_handler', E_ALL | E_STRICT );

// Load our testing globals from the testmore project
require_once implode(DIRECTORY_SEPARATOR,array(dirname(__FILE__),"..","testlib","testmore.php"));

// Install our auto loader
require_once implode(DIRECTORY_SEPARATOR,array(dirname(__FILE__),"..","Modyllic", "AutoLoader.php"));
Modyllic_AutoLoader::install();

