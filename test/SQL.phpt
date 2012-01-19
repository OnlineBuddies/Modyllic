#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../testlib/testmore.php";

plan(20);

require_ok("Modyllic/SQL.php");

$parser = new Modyllic_Parser();

$sql = <<<EOSQL
CREATE TABLE test ( id INT );
CREATE TABLE test2 ( id INT );
EOSQL;

$schema1 = $parser->parse( $sql );

diag("Tests of the first schema" );

ok( $schema1 instanceOf Modyllic_Schema, "Parse returns an Modyllic_Schema object" );

is( count($schema1->tables), 2, "Parsed two table" );
is( count($schema1->routines), 0, "Parsed no routines" );

$test_table = $schema1->tables['test'];
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

$test_sql = <<<EOSQL
CREATE TABLE test (
    id INT
) ENGINE=InnoDB
EOSQL;
