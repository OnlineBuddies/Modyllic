#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../testlib/testmore.php";

plan(20);

require_ok("Modyllic/Parser.php");

$parser = new Modyllic_Parser();

$sql = <<<EOSQL
CREATE TABLE test ( id INT );
CREATE TABLE test2 ( id INT );
EOSQL;

$schema = $parser->parse( $sql );

diag("Tests of the first schema" );

ok( $schema instanceOf Modyllic_Schema, "Parse returns an Modyllic_Schema object" );

is( count($schema->tables), 2, "Parsed two table" );
is( count($schema->routines), 0, "Parsed no routines" );

$test_table = $schema->tables['test'];
ok( $test_table instanceOf Modyllic_Table, "We got an Modyllic_Schema_Table object for the table test" );
is( $test_table->name, "test", "The name attribute got set" );
is( $test_table->engine, "InnoDB", "The default engine got set" );
is( $test_table->charset, "utf8", "The default charset got set" );
is( count($test_table->columns), 1, "One column found" );
is( count($test_table->indexes), 0, "No indexes found" );
$column = $test_table->columns['id'];
ok( $column instanceOf Modyllic_Column, "Column id isa Modyllic_Column" );
is( $column->name, "id", "Column name set" );
is( count($column->aliases), 0, "No aliases" );
ok( $column->type instanceOf Modyllic_Integer, "Column type set" );
ok( $column->null, "Column is nullable by default" );
is( $column->default, "NULL", "Column is nullable and therefor has an implicit default of NULL" );
ok( ! $column->auto_increment,  "Column is not autoincrement" );
ok( is_null($column->on_update), "No on update" );
is( $column->docs, "", "No docs" );
is( $column->after, "", "Column is the first column" );

$sql = <<<EOSQL
CREATE TABLE test (
    id INT,
    KEY (id)
) ENGINE=InnoDB
EOSQL;
$schema = $parser->parse($sql);
$table = $schema->tables['test'];
$index = array_pop($table->indexes);
is( $index->name, "id", "Generated index name is correct");
is( $index->dynamic_name, true, "Generated index name is flagged as dynamic");

$msg = "Trailing words on DELIMITERs produce reasonable error messages";
try {
    $sql = 'DELIMITER ; |';
    $schema = $parser->parse($sql);
    fail($msg);
}
catch (Modyllic_Exception $e) {
    if ( ! ok( ! preg_match("/Modyllic_Token/",$e->getMessage()), $msg ) ) {
        foreach (explode("\n",$e->getMessage()) as $line) {
            diag($line);
        }
    }
}
