#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once implode(DIRECTORY_SEPARATOR, array(
    dirname(__FILE__), '..', 'test_environment.php' ));

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
$reserved     = array( "CREATE" );
$not_reserved = array( "WIBBLE" );


    // Unquoted identifiers are either:  http://dev.mysql.com/doc/refman/5.5/en/identifiers.html
    // An alpha+dollar+underscore followed by any number of digit+alpha+dollar+underscore
    // OR
    // Some number of digits followed a alpha+dollar+underscore followed by any number of digit+alpha+dollar+underscore
$valid_ident = array( "foo", '$', '_', '$$_', 'abc123', '_789', '23$', '15_27', '42a' );
$not_valid_ident = array( "23", '@', '!@$', 'ab&' );

plan( count($ident_tests) + count($str_tests) + count($reserved) + count($not_reserved) + count($valid_ident) + count($not_valid_ident) );

foreach ($ident_tests as $plain=>$quoted) {
    is( Modyllic_SQL::quote_ident($plain), $quoted, "Identifier $plain");
}

foreach ($str_tests as $plain=>$quoted) {
    is( Modyllic_SQL::quote_str($plain), $quoted, "String $plain");
}

foreach ($reserved as $word ) {
    ok( Modyllic_SQL::is_reserved($word), "$word is a reserved word" );
}

foreach ($not_reserved as $word ) {
    ok( ! Modyllic_SQL::is_reserved($word), "$word is not a reserved word" );
}

foreach ($valid_ident as $ident) {
    ok( Modyllic_SQL::valid_ident($ident), "$ident is a valid identifier name" );
}

foreach ($not_valid_ident as $ident) {
    ok( ! Modyllic_SQL::valid_ident($ident), "$ident is not a valid identifier name" );
}
