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
     array(        "CURRENT_TIME", new Modyllic_Token_Reserved(0,"CURRENT_TIME"),        "CURRENT_TIME" ), 
     array(                     0, new Modyllic_Token_Num(0,0),                          "'0000-00-00 00:00:00'" ),
     array(                   "0", null,                                                 "'0000-00-00 00:00:00'" ),
     array(          "2012-01-01", new Modyllic_Token_String(0,"'2012-01-01'"),          "'2012-01-01 00:00:00'" ),
     array(            "2012-1-1", new Modyllic_Token_String(0,"'2012-1-1'"),            "'2012-01-01 00:00:00'" ),
     array(               "5-1-1", new Modyllic_Token_String(0,"'5-1-1'"),               "'0005-01-01 00:00:00'" ),
     array(        "2012-01-01 7", new Modyllic_Token_String(0,"'2012-01-01 7'"),        "'2012-01-01 07:00:00'" ),
     array(      "2012-01-01 7:1", new Modyllic_Token_String(0,"'2012-01-01 7:1'"),      "'2012-01-01 07:01:00'" ),
     array(    "2012-01-01 7:1:8", new Modyllic_Token_String(0,"'2012-01-01 7:1:8'"),    "'2012-01-01 07:01:08'" ),
     array( "2012-01-01 07:01:08", new Modyllic_Token_String(0,"'2012-01-01 07:01:08'"), "'2012-01-01 07:01:08'" ),
     );

$invalid_date_tests = array(
    new Modyllic_Token_String(0,"'0'"),
    new Modyllic_Token_Num(0,"1.0"),
    5,
    "7",
    new Modyllic_Token_String(0,"'0.1'"),
    "2012-01-01T07:01:08",
    new Modyllic_Token_String(0,"'2012-01-01T07:01:08'"),
    "2012-01",
    new Modyllic_Token_String(0,"'2012-01'"),
    "asdlkjfd",
    new Modyllic_Token_Ident(0, "abc"),
    );

plan( count($invalid_date_tests) + (2*count($normalization_tests)) + 6 );

$datetype1 = Modyllic_Type::create("DATETIME");
$datetype2 = Modyllic_Type::create("DATETIME");
$strtype   = Modyllic_Type::create("CHAR");
$strtype->length = 17;

is( $datetype1->to_sql(), "DATETIME", "to_sql" );
ok( $datetype2->equal_to($datetype2), "equal_to" );
ok( ! $datetype2->equal_to($strtype), "! equal_to" );
ok( $datetype1->isa_equivalent($datetype2), "isa_equivalent" );
ok( ! $datetype1->isa_equivalent($strtype), "! isa_equivalent" );
ok( $datetype1->is_valid(), "is_valid" );

foreach ($normalization_tests as $test) {
    list($value, $token, $expected) = $test;
    is($datetype1->normalize($value), $expected, "Normalize a literal $value");
    if ( isset($token) ) {
        is($datetype1->normalize($token), $expected, "Normalize a $token");
    }
    else {
        ok(true, "#");
    }
    
}

foreach ($invalid_date_tests as $test) {
    $msg = "Trying to normalize a ". (is_object($test)?"$test":"literal $test"). " should fail";
    try {
        $normalized = $datetype1->normalize($test);
        fail($msg);
    }
    catch (Exception $e) {
        pass($msg);
    }
}

