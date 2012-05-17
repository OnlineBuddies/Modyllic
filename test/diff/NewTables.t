#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once implode( DIRECTORY_SEPARATOR, array(dirname(__FILE__), "..", "test_environment.php") );

plan( 3 );

$parser = new Modyllic_Parser();

$schema1_sql = <<< EOSQL
CREATE DATABASE test1;
EOSQL;

$schema2_sql = <<< EOSQL
CREATE DATABASE test1;
CREATE TABLE table1 ( id int PRIMARY KEY );
EOSQL;

$schema1 = $parser->parse($schema1_sql);
$schema2 = $parser->parse($schema2_sql);

$diff = new Modyllic_Diff($schema1,$schema2);

$changes = $diff->changeset();

is($changes->has_changes(), true, "Adding a table changes the schema" );

is(count($changes->add['tables']), 1, "One new table found in the changeset");

is(current($changes->add['tables'])->name, 'table1', "The new table is our new table");
