#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../test_environment.php";

plan( 5 );

$parser = new Modyllic_Parser();

$schema1_sql = <<< EOSQL
DELIMITER ;;
CREATE DATABASE test1 ;;
CREATE PROCEDURE foo()
BEGIN
    SELECT 1;
END ;;
EOSQL;

$schema2_sql = <<< EOSQL
DELIMITER ;;
CREATE DATABASE test1 ;;
CREATE PROCEDURE foo(id INT)
BEGIN
    SELECT 1;
END ;;
CREATE PROCEDURE bar()
BEGIN
    SELECT 2;
END ;;
EOSQL;

$schema1 = $parser->parse($schema1_sql);
$schema2 = $parser->parse($schema2_sql);

$diff = new Modyllic_Diff($schema1,$schema2);

$changes = $diff->changeset();

is($changes->has_changes(), true, "Our changeset contains changes" );

is(count($changes->add['routines']), 1, "One new routine found in the changeset");

is(current($changes->add['routines'])->name, 'bar', "The new routine is bar");

is(count($changes->update['routines']), 1, "One updated routine found in the changeset");

is(current($changes->update['routines'])->name, 'foo', "The updated routine is foo");
