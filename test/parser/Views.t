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

$view1_sql = <<< EOSQL
CREATE ALGORITHM=UNDEFINED
       DEFINER=`abc`@`def` SQL SECURITY DEFINER
       VIEW test AS SELECT * from foo;
EOSQL;

$schema = $parser->parse($view1_sql);

ok( isset($schema->views['test']), "View created" );

is( $schema->views['test']->name, 'test', "View name" );

is ( $schema->views['test']->def, ' AS SELECT * from foo', "View defined" );
