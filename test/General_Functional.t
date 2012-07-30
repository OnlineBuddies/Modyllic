#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/test_environment.php";

plan(33);

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
ok( $test_table instanceOf Modyllic_Schema_Table, "We got an Modyllic_Schema_Table object for the table test" );
is( $test_table->name, "test", "The name attribute got set" );
is( $test_table->engine, "InnoDB", "The default engine got set" );
is( $test_table->charset, "utf8", "The default charset got set" );
is( count($test_table->columns), 1, "One column found" );
is( count($test_table->indexes), 0, "No indexes found" );
$column = $test_table->columns['id'];
ok( $column instanceOf Modyllic_Schema_Column, "Column id isa Modyllic_Schema_Column" );
is( $column->name, "id", "Column name set" );
is( count($column->aliases), 0, "No aliases" );
ok( $column->type instanceOf Modyllic_Type_Integer, "Column type set" );
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


if ( is_dir(dirname(__FILE__)."/test_schema") ) {
    list( $source, $loader ) = Modyllic_Loader::determine_loader( dirname(__FILE__)."/test_schema/test1.sql" );
    is( $loader, "Modyllic_Loader_File", "Plain file schema are loaded with File" );
    list( $source, $loader ) = Modyllic_Loader::determine_loader( dirname(__FILE__)."/test_schema/test2.sql" );
    is( $loader, "Modyllic_Loader_File", "Symlinks to plain file schema are loaded with File" );
    list( $source, $loader ) = Modyllic_Loader::determine_loader( dirname(__FILE__)."/test_schema/test3" );
    is( $loader, "Modyllic_Loader_Dir", "Directory schema are loaded with Dir" );
    list( $source, $loader ) = Modyllic_Loader::determine_loader( dirname(__FILE__)."/test_schema/test4" );
    is( $loader, "Modyllic_Loader_Dir", "Symlinks to directory schema are loaded with Dir" );
    list( $source, $loader ) = Modyllic_Loader::determine_loader( "mysql:dbname=test" );
    is( $loader, "Modyllic_Loader_DB", "DSN schema are loaded with DB" );
    list( $source, $loader ) = Modyllic_Loader::determine_loader( dirname(__FILE__)."/test_schema/invalid" );
    is( $loader, null, "Invalid schema result in no loader" );

    $schema = Modyllic_Loader::load( array( dirname(__FILE__)."/test_schema/test1.sql" ) );
    is( get_class($schema), "Modyllic_Schema", "File loaded a plain file" );
    $schema = Modyllic_Loader::load( array( dirname(__FILE__)."/test_schema/test2.sql" ) );
    is( get_class($schema), "Modyllic_Schema", "File loaded a symlink");
    $schema = Modyllic_Loader::load( array( dirname(__FILE__)."/test_schema/test3/" ) );
    is( get_class($schema), "Modyllic_Schema", "Dir loaded a directory");
    $schema = Modyllic_Loader::load( array( dirname(__FILE__)."/test_schema/test4" ) );
    is( get_class($schema), "Modyllic_Schema", "Dir loaded a symlink to a directory");
    $msg = "Invalid schema throw an error";
    try {
        $schema = Modyllic_Loader::load( array( dirname(__FILE__)."/test_schema/invalid" ) );
        fail($msg);
    }
    catch (Modyllic_Loader_Exception $e) {
        pass($msg);
    }
}
else {
    skip("Test schema not found, not doing loader tests",6);
}

