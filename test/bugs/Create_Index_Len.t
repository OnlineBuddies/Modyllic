#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../../testlib/testmore.php";

require_once "Modyllic/SQL.php";
require_once "Modyllic/Parser.php";
require_once "Modyllic/Schema.php";

$parser = new Modyllic_Parser();
$schema = new Modyllic_Schema();
$schema = $parser->partial($schema, "
CREATE TABLE a (
    b VARCHAR(60) NOT NULL,
    KEY (b(30))
)
", "example", ";" );

plan(5);
ok( isset($schema->tables['a']), "Table 'a' exists" );
$table = $schema->tables['a'];
is( count($table->indexes), 1, "There is 1 index" );
$index = array_pop( array_values($table->indexes)  );
is( count($index->columns), 1, "It indexes one column" );
foreach ($index->columns as $name=>$length) {
    is( $name, "b", "The indexed column name is correct");
    is( $length, 30, "The indexed column length is correct");
}
