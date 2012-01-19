#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../testlib/testmore.php";

$ident_tests = array(
    'abc123' => 'abc123',
    '$foo'   => '$foo',
    '_bar'   => '_bar',
    '0abc27' => '0abc27',
    '123'    => '`123`',
    'ABC'    => 'ABC',
    'TIME'   => '`TIME`',
);
$str_tests = array(
    "abc"         => "'abc'",
    "abc'123"     => "'abc''123'",
    "ab\\%"       => "'ab\\%'",
    "ab\\_"       => "'ab\\_'",
    "ab\\$"       => "'ab\\\\$'",
    "null".chr(0) => "'null\\0'",
    "bs".chr(8)   => "'bs\\b'",
    "nl".chr(10)  => "'nl\\n'",
    "cr".chr(13)  => "'cr\\r'",
    "tab".chr(9)  => "'tab\\t'",
    "eot".chr(26) => "'eot\\Z'",
);

plan( 1 + count($ident_tests) + count($str_tests) );

require_ok("Modyllic/SQL.php");

foreach ($ident_tests as $plain=>$quoted) {
    is( Modyllic_SQL::quote_ident($plain), $quoted, "Identifier $plain");
}

foreach ($str_tests as $plain=>$quoted) {
    is( Modyllic_SQL::quote_str($plain), $quoted, "String $plain");
}
