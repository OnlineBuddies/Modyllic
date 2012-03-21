#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../../testlib/testmore.php";

plan( 18 );

require_ok("Modyllic/Parser.php");

$parser = new Modyllic_Parser();

$trig1_sql = <<< EOSQL
DELIMITER ;;
CREATE TABLE bar (id int);;
CREATE TRIGGER trig1 
BEFORE INSERT ON bar 
FOR EACH ROW
BEGIN
    CALL do_something();
END ;;
EOSQL;

$trig2_sql = <<< EOSQL
CREATE TABLE bar (id int);
CREATE DEFINER = abc@localhost TRIGGER trig1 
AFTER UPDATE ON bar 
FOR EACH ROW 
INSERT INTO foo SET when=NOW();
EOSQL;

$schema1 = $parser->parse($trig1_sql);
$schema2 = $parser->parse($trig2_sql);

require_ok("Modyllic/Diff.php");

$diff = new Modyllic_Diff($schema1,$schema2);

$changes = $diff->changeset();
is( $changes->has_changes(), true, "Trigger only schema changes differ" );

foreach (array( "tables", "routines", "events", "views" ) as $thing) {
    is( count($changes->add[$thing]), 0, "Added no $thing" );
    is( count($changes->update[$thing]), 0, "Updated no $thing" );
    is( count($changes->remove[$thing]), 0, "Removed no $thing" );
}

is( count($changes->add['triggers']), 0, "Added no triggers" );
is( count($changes->update['triggers']), 1, "Updated one trigger" );
is( count($changes->remove['triggers']), 0, "Removed no triggers" );
