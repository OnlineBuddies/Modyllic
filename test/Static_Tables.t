#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../testlib/testmore.php";

plan(11);

require_ok("Modyllic/Parser.php");


$parser = new Modyllic_Parser();

$sql = <<<EOSQL
CREATE TABLE test ( id INT );
TRUNCATE TABLE test;
INSERT INTO test (id) VALUES (1);
EOSQL;

$schema = $parser->parse( $sql );
ok( isset($schema->tables['test']), "Test table created" );
$test = $schema->tables['test'];
is( $test->static, true, "Test table is flagged static" );
is( count($test->data), 1, "One row of test data was created" );
is( $test->data[0]['id'], 1, "The id column of the test data is set" );

$sql = <<<EOSQL
CREATE TABLE test ( id INT );
TRUNCATE TABLE test;
INSERT INTO test SET id=1;
EOSQL;

$schema = $parser->parse( $sql );
$test = $schema->tables['test'];
is( count($test->data), 1, "One row of test data was created using update style insert" );
is( $test->data[0]['id'], 1, "The id column of the test data is set using update style insert" );

$sql = <<<EOSQL
CREATE TABLE test ( id INT );
TRUNCATE TABLE test;
INSERT INTO test (id) VALUES (1),(2),(10);
EOSQL;

$schema = $parser->parse( $sql );
$test = $schema->tables['test'];
is( count($test->data), 3, "Three rows of test data were created using an extended insert" );
is( $test->data[0]['id'], 1, "The first row's id is set" );
is( $test->data[1]['id'], 2, "The second row's id is set" );
is( $test->data[2]['id'], 10, "The third row's id is set" );

