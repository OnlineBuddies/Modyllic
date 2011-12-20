#!/usr/bin/env php
<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../build/test.php";
require_once "SQL.php";

$num_tests = array(
   "Positive Integer"  => array( "sql"=>    "50",          "value"=>    "50"          ),
   "Negative Integer"  => array( "sql"=>   "-25",          "value"=>   "-25"          ),
   "Positive Float"    => array( "sql"=>    "50.3",        "value"=>    "50.3"        ),
   "Positive Dec Only" => array( "sql"=>    "50.3",        "value"=>    "50.3"        ),
   "Float with e+"     => array( "sql"=>"-32032.6809e+10", "value"=>"-32032.6809e+10" ),
   "Float with e-"     => array( "sql"=>"-32032.6809e-10", "value"=>"-32032.6809e-10" ),
   "Float with e"      => array( "sql"=>"-32032.6809e10",  "value"=>"-32032.6809e10"  ),
   );
$str_tests = array(
   "Basic String"    => array( "sql"=>"'a string'",       "value"=>"'a string'",       "unquoted"=>"a string" ),
   "Double Quotes"   => array( "sql"=>'"a string"',       "value"=>'"a string"',       "unquoted"=>"a string" ),
   "Double Strings"  => array( "sql"=>"'a' ' ' 'string'", "value"=>"'a string'",       "unquoted"=>"a string" ),
   "String Escapes"  => array( "sql"=>"'test\\t'",        "value"=>"'test\\t'",        "unquoted"=>"test\t" ),
   "Special Escapes" => array( "sql"=>"'test\\_\\%'",     "value"=>"'test\\_\\%'",     "unquoted"=>"test\\_\\%" ),
   "Non Escapes"     => array( "sql"=>"'test\\'\\a\\\\'", "value"=>"'test\\'\\a\\\\'", "unquoted"=>"test'a\\" ),
   "Quote Escapes"   => array( "sql"=>"'test''this'",     "value"=>"'test''this'",     "unquoted"=>"test'this" ),
   "Unicode String"  => array( "sql"=>"'á ß†®îñg'",       "value"=>"'á ß†®îñg'",       "unquoted"=>"á ß†®îñg" ),
   );
$ident_tests = array(
    "Basic Ident"          => array( "sql"=>"foo",      "value"=>"foo" ),
    "Quoted Ident"         => array( "sql"=>"`foo`",    "value"=>"foo" ),
    "Quoted Ident w/Space" => array( "sql"=>"`fo oo`",  "value"=>"fo oo" ),
    "Quoted Ident w/Quote" => array( "sql"=>"`fo``oo`", "value"=>"fo`oo" ),
    "Quoted Ident w/Slash" => array( "sql"=>"`fo\`oo`", "value"=>"fo`oo" ),
    "Unicode Ident"        => array( "sql"=>"`ƒöó`",    "value"=>"ƒöó" ),
    );
$comment_tests = array(
    "Hash Comments"    => array( "sql"=>"# this is a test",     "value"=>"this is a test" ),
    "C-Style Comments" => array( "sql"=>"/* this is a test */", "value"=>"this is a test" ),
    "SQL Commetns"     => array( "sql"=>"-- this is a test",    "value"=>"this is a test" ),
    );

$t = new mh_test( count($num_tests)*2 + count($str_tests)*3 + count($ident_tests)*4 + count($comment_tests)*2 + 12 );

foreach ( $num_tests as $name=>$test ) {
    $tok = new SQL_Tokenizer($test['sql']);
    $token = $tok->next();
    $t->is_true( $token instanceOf SQL_Token_Num, "$name is a Num token" );
    $t->is( $token->value(), $test['value'], "$name has the right value" );
}

foreach ( $str_tests as $name=>$test ) {
    $tok = new SQL_Tokenizer($test['sql']);
    $token = $tok->next();
    $t->is_true( $token instanceOf SQL_Token_String, "$name is a Str token" );
    $t->is( $token->value(), $test['value'], "$name has the right value" );
    $t->is( $token->unquote(), $test['unquoted'], "$name unquotes correctly" );
}

foreach ( $ident_tests as $name=>$test ) {
    $tok = new SQL_Tokenizer($test['sql']);
    $token = $tok->next();
    $t->is_true( $token instanceOf SQL_Token_Ident, "$name is an Ident token" );
    if ( $token->value() != $token->literal() ) {
        $t->is_true( $token instanceOf SQL_Token_Quoted_Ident, "$name is a Quoted Ident token" );
    }
    else {
        $t->is_false( $token instanceOf SQL_Token_Quoted_Ident, "$name isn't a Quoted Ident token" );
    }
    $t->is( $token->value(), $test['value'], "$name has the right value" );
    $t->is( $token->literal(), $test['sql'], "$name is unchanged in its literal form" );
}

foreach ( $comment_tests as $name=>$test ) {
    $tok = new SQL_Tokenizer($test['sql']);
    $token = $tok->next();
    $t->is_true( $token instanceOf SQL_Token_Comment, "$name is a Comment token" );
    $t->is( $token->value(), $test['value'], "$name has the right value" );
}

$tok = new SQL_Tokenizer("create");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_Reserved, "CREATE is Reserved token" );
$t->is( $token->value(), "create", "CREATE has the right value" );
$t->is( $token->token(), "CREATE", "CREATE is all caps as a token" );

$tok = new SQL_Tokenizer(" \t \n test");
$token = $tok->next(TRUE);
$t->is_true( $token instanceOf SQL_Token_Whitespace, "Whitespace got tokenized" );
$t->is( $token->value(), " \t \n ", "The whitespace is what we expected" );

$tok = new SQL_Tokenizer(" \t \n test");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_Ident, "Not asking for whitespace correctly ignored it" );

$tok = new SQL_Tokenizer(":");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_Symbol, "Arbitrary symbol is a Symbol token" );

$tok = new SQL_Tokenizer("/*!12345 test */");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_Ident, "MySQL conditional comments are handled correctly" );

$tok = new SQL_Tokenizer("");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_EOF, "Empty string is immediate EOF" );
$t->is_true( $token instanceOf SQL_Token_EOC, "Empty string is EOC" );

$tok = new SQL_Tokenizer(";");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_EOC, "Delimiter produces EOC" );

$tok = new SQL_Tokenizer("ó");
$token = $tok->next();
$t->is_true( $token instanceOf SQL_Token_Error, "Unicode bareword character produces syntax error" );

## todo, explicit tests for:
## set_delimiter
## rest
## line
## col
## context
## inject
## next
## peek_next
