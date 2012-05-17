#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once implode( DIRECTORY_SEPARATOR, array(dirname(__FILE__), "..", "test_environment.php") );

plan( 16 );

$parser = new Modyllic_Parser();

$drop1_sql = <<< EOSQL
DELIMITER ;;

CREATE DATABASE db1;;

CREATE TABLE table1 (
    col1 INT PRIMARY KEY,
    col2 CHAR(30),
    KEY col2 (col2)) ;;

CREATE EVENT event1 ON SCHEDULE EVERY 1 YEAR DO
BEGIN
    SELECT 1;
END ;;

CREATE TRIGGER trigger1 BEFORE INSERT ON table1 FOR EACH ROW SET col2=col1 ;;

CREATE FUNCTION routine1()
RETURNS INT
BEGIN
    RETURN 1;
END ;;

CREATE PROCEDURE routine2()
COMMENT 'routine1 comment'
CALL routine1();;

CREATE VIEW view1 AS SELECT * from table1;;

EOSQL;

$schema = $parser->parse($drop1_sql);

ok( isset($schema->views['view1']), "We created a view" );
$parser->partial($schema,"DROP VIEW view1");
is( count($schema->views), 0, "We dropped a view" );

ok( isset($schema->routines['routine1']), "We created a routine" );
ok( isset($schema->routines['routine2']), "We created routine without a begin block" );
$parser->partial($schema,"DROP PROCEDURE routine1");
ok( ! isset($schema->routines['routine1']), "We dropped a routine" );

ok( isset($schema->triggers['trigger1']), "We created a trigger" );
$parser->partial($schema,"DROP TRIGGER trigger1");
is( count($schema->triggers), 0, "We dropped a trigger" );

ok( isset($schema->events['event1']), "We created a event" );
$parser->partial($schema,"DROP EVENT event1");
is( count($schema->events), 0, "We dropped a event" );

ok( isset($schema->tables['table1']), "We created a table" );
ok( isset($schema->tables['table1']->indexes["col2"]), "We created an index" );
$parser->partial($schema,"DROP INDEX col2 ON table1");
ok( ! isset($schema->tables['table1']->indexes["col2"]), "We dropped an index" );

$parser->partial($schema,"DROP TABLE table1");
is( count($schema->tables), 0, "We dropped a table" );

is( $schema->name, 'db1', "Our database is named" );
$parser->partial($schema,"DROP DATABASE db1" );
is( $schema->name, 'database', "Dropping the database reset its name");
is( count($schema->routines), 0, "Dropping the database dropped the last routine");
