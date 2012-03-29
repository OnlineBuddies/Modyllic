#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../../testlib/testmore.php";

$delim = "DELIMITER ;;\n";
$create_sql = array();
$drop_sql = array();
$create_sql[] = <<< EOSQL
CREATE TRIGGER trig1
BEFORE INSERT ON bar
FOR EACH ROW BEGIN
    CALL do_something();
END
EOSQL;
$drop_sql[] = "DROP TRIGGER IF EXISTS trig1";
$create_sql[] = <<< EOSQL
CREATE TRIGGER trig2
AFTER DELETE ON bar
FOR EACH ROW INSERT INTO foo (id) VALUES (27)
EOSQL;
$drop_sql[] = "DROP TRIGGER IF EXISTS trig2";

plan( 10 + count($create_sql) );

require_ok("Modyllic/Parser.php");

$parser = new Modyllic_Parser();

$schema = $parser->parse($delim.implode(";;",$create_sql));

require_ok("Modyllic/Generator/SQL.php");

$gen = new Modyllic_Generator_SQL();
$gen_sql = $gen->create_triggers( $schema->triggers )->sql_commands();
is( count($gen_sql), count($create_sql), "Generated all the CREATEs" );
foreach ($create_sql as $num=>$sql) {
    is( $gen_sql[$num], $sql, "Trigger ".($num+1).": Generated CREATE SQL" );
}

$gen = new Modyllic_Generator_SQL();
$gen_sql = $gen->drop_triggers( $schema->triggers )->sql_commands();
is( count($gen_sql), count($drop_sql), "Generated all the DROPs" );
foreach (array_reverse($drop_sql) as $num=>$sql) { // Drops are in reverse order of creates
    is( $gen_sql[$num], $sql, "Trigger ".($num+1).": Generated DROP SQL" );
}

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

$gen = new Modyllic_Generator_SQL();
$sql = $gen->alter( $diff, array('triggers') )->sql_commands();

is( count($sql), 2, "Diff requires two SQL commands" );

$drop_sql = "DROP TRIGGER IF EXISTS trig1";
is( $sql[0], $drop_sql, "Drop trigger part of update" );

$create_sql = <<<EOSQL
CREATE TRIGGER trig1
AFTER UPDATE ON bar
FOR EACH ROW INSERT INTO foo SET when=NOW()
EOSQL;
is( $sql[1], $create_sql, "Create trigger part of update" );
