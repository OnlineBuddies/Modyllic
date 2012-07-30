#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../test_environment.php";

plan( 15 );

$parser = new Modyllic_Parser();

$trig1_sql = <<< EOSQL
DELIMITER ;;
CREATE TABLE bar (id int) ;;
CREATE TRIGGER trig1
BEFORE INSERT ON bar
FOR EACH ROW
BEGIN
    CALL do_something();
END ;;
EOSQL;

$schema = $parser->parse($trig1_sql);
$trig1 = @$schema->triggers['trig1'];
ok( isset($trig1), "Trigger 1: Parsed out a trigger" );
is( $trig1->time, 'BEFORE', "Trigger 1: Parsed time to trigger" );
is( $trig1->event, 'INSERT', "Trigger 1: Parsed event to trigger on" );
is( $trig1->table, 'bar', "Trigger 1: Parsed table to trigger on" );
like( $trig1->body, "/do_something/", "Trigger 1: Parsed action to take" );

$trig2_sql = <<< EOSQL
CREATE TABLE bar (id int);
CREATE DEFINER = abc@localhost TRIGGER trig2
AFTER UPDATE ON bar
FOR EACH ROW
INSERT INTO foo SET when=NOW();
EOSQL;
$schema = $parser->parse($trig2_sql);
$trig2 = @$schema->triggers['trig2'];
ok( isset($trig2), "Trigger 2: Parsed out a trigger" );
is( $trig2->time, 'AFTER', "Trigger 2: Parsed time to trigger" );
is( $trig2->event, 'UPDATE', "Trigger 2: Parsed event to trigger on" );
is( $trig2->table, 'bar', "Trigger 2: Parsed table to trigger on" );
like( $trig2->body, "/INSERT INTO/", "Trigger 2: Parsed action to take" );

$trig3_sql = <<< EOSQL
CREATE TABLE bar (id int);
CREATE DEFINER=CURRENT_USER TRIGGER trig3
BEFORE DELETE ON bar
FOR EACH ROW
CALL something();
EOSQL;
$schema = $parser->parse($trig3_sql);
$trig3 = @$schema->triggers['trig3'];
ok( isset($trig3), "Trigger 3: Parsed out a trigger" );
is( $trig3->time, 'BEFORE', "Trigger 3: Parsed time to trigger" );
is( $trig3->event, 'DELETE', "Trigger 3: Parsed event to trigger on" );
is( $trig3->table, 'bar', "Trigger 3: Parsed table to trigger on" );
like( $trig3->body, "/CALL something/", "Trigger 3: Parsed action to take" );
