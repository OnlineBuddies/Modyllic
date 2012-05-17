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

if (getenv("TEST_COVERAGE")) {
    $__coverage = new PHP_CodeCoverage;
    $__coverage->start('test');
    $__coverage->filter()->addDirectoryToWhitelist(realpath(dirname(__FILE__)."/../Modyllic"));
    register_shutdown_function('__end_coverage');
}

function __end_coverage() {
    global $__coverage;
    $__coverage->stop();
    $coverageDir = dirname(__FILE__)."/../tmp/test/coverage";
    @mkdir($coverageDir, 0777, true);
    if (!is_dir($coverageDir)) throw new Exception("Could not create $coverageDir");
    $f = fopen($coverageDir."/".str_replace('/', '_', $_SERVER['SCRIPT_NAME']).".cov", "w");
    fwrite($f, serialize($__coverage));
    fclose($f);
}
