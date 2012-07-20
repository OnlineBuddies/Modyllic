#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once implode( DIRECTORY_SEPARATOR, array(dirname(__FILE__), "..", "test_environment.php") );

plan( 3 );

$parser = new Modyllic_Parser();

$error1_sql = <<< EOSQL
INTEGER;
EOSQL;

$msg = "A non-command reserved word throws an exception";
try {
    $schema = $parser->parse($error1_sql);
    fail($msg);
}
catch (Modyllic_Exception $e) {
    like($e->getMessage(),"/Unsupported SQL command/", $msg);
}

$error2_sql = <<< EOSQL
INVALIDXYZZY COMMAND;
EOSQL;

$msg = "A nonsense word throws an exception";
try {
    $schema = $parser->parse($error2_sql);
    fail($msg);
}
catch (Modyllic_Exception $e) {
    like($e->getMessage(),"/Expected reserved word, got/", $msg);
}

$error3_sql = <<< EOSQL
CREATE DATABASE test1;
USE test2;
EOSQL;
$msg = "Can't USE a different database then you CREATE";
try {
    $schema = $parser->parse($error3_sql);
    fail($msg);
}
catch (Modyllic_Exception $e) {
    like($e->getMessage(),"/Can't USE test2 when creating test1/", $msg);
}
