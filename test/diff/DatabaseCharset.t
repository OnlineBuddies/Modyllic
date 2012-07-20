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
CREATE DATABASE test1 CHARSET utf8 COLLATE utf8_general_ci ;
EOSQL;

$schema2_sql = <<< EOSQL
CREATE DATABASE test1 CHARSET latin1 COLLATE latin1_general_ci ;
EOSQL;

$schema1 = $parser->parse($schema1_sql);
$schema2 = $parser->parse($schema2_sql);

$diff = new Modyllic_Diff($schema1,$schema2);

$changes = $diff->changeset();

is($changes->has_changes(), true, "Changing charset and collation changes schema" );

is($changes->schema->charset, 'latin1', "Changed charset to latin1");

is($changes->schema->collate, 'latin1_general_ci', "And changed the collation as well");
