#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../test_environment.php";

plan( 3 );

$parser = new Modyllic_Parser();

$error1_sql = <<< EOSQL
INTEGER;
EOSQL;

$schema = $parser->parse($error1_sql);
@like($schema->errors[0],"/Unsupported SQL command/", "A non-command reserved word throws an exception");

$error2_sql = <<< EOSQL
INVALIDXYZZY COMMAND;
EOSQL;

$schema = $parser->parse($error2_sql);
@like($schema->errors[0],"/Unsupported SQL command/", "A nonsense word throws an exception");

$error3_sql = <<< EOSQL
CREATE DATABASE test1;
USE test2;
EOSQL;
$msg = "Can't USE a different database then you CREATE";

$schema = $parser->parse($error3_sql);
@like($schema->errors[0],"/Can't USE test2 when creating test1/", "Can't USE a different database then you CREATE");
