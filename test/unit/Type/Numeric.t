#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../../test_environment.php";

$normalization_tests = array(
     array("3.6729999999999996", new Modyllic_Token_Num(0, "3.6729999999999996"), "3.6729999999999996"),
     );


plan( (2*count($normalization_tests)) );

$numeric = Modyllic_Type::create("NUMERIC");

foreach ($normalization_tests as $test) {
    list($value, $token, $expected) = $test;
    var_dump($value);
    var_dump($token);
    var_dump($expected);
    is(sprintf("%s", $numeric->normalize($value)), $expected, "Normalize a literal $value");
    if ( isset($token) ) {
        is(sprintf("%s", $numeric->normalize($token)), $expected, "Normalize a $token");
    }
    else {
        ok(true, "#");
    }
}

