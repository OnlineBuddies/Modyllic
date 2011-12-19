#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../build/test.php";
require_once "OLB/SQL.php";

$t = new mh_test(19);


$parser = new SQL_Parser();

$sql = <<<EOSQL
CREATE TABLE test ( id INT );
CREATE TABLE test2 ( id INT );
EOSQL;

$schema1 = $parser->parse( $sql );

$t->diag("Tests of the first schema" );

$t->ok( $schema1 instanceOf SQL_Schema, "Parse returns an SQL_Schema object" );

$t->is( count($schema1->tables), 2, "Parsed two table" );
$t->is( count($schema1->routines), 0, "Parsed no routines" );

$test_table = $schema1->tables['test'];
$t->ok( $test_table instanceOf SQL_Table, "We got an SQL_Schema_Table object for the table test" );
$t->is( $test_table->name, "test", "The name attribute got set" );
$t->is( $test_table->engine, "InnoDB", "The default engine got set" );
$t->is( $test_table->charset, "utf8", "The default charset got set" );
$t->is( count($test_table->columns), 1, "One column found" );
$t->is( count($test_table->indexes), 0, "No indexes found" );
$column = $test_table->columns['id'];
$t->ok( $column instanceOf SQL_Column, "Column id isa SQL_Column" );
$t->is( $column->name, "id", "Column name set" );
$t->is( count($column->aliases), 0, "No aliases" );
$t->ok( $column->type instanceOf SQL_Integer, "Column type set" );
$t->is_true( $column->null, "Column is nullable by default" );
$t->is( $column->default, "NULL", "Column is nullable and therefor has an implicit default of NULL" );
$t->is_false( $column->auto_increment,  "Column is not autoincrement" );
$t->is_null( $column->on_update, "No on update" );
$t->is( $column->docs, "", "No docs" );
$t->is( $column->after, "", "Column is the first column" );

$test_sql = <<<EOSQL
CREATE TABLE test (
    id INT
) ENGINE=InnoDB
EOSQL;
