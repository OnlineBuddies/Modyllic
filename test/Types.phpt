#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../testlib/testmore.php";

$sql_types = array(
    "BIT"              => "SQL_Bit",
    "BOOL"             => "SQL_TinyInt",
    "BOOLEAN"          => "SQL_TinyInt",
    "TINYINT"          => "SQL_TinyInt",
    "SMALLINT"         => "SQL_SmallInt",
    "MEDIUMINT"        => "SQL_MediumInt",
    "INT"              => "SQL_Integer",
    "INTEGER"          => "SQL_Integer",
    "BIGINT"           => "SQL_BigInt",
    "SERIAL"           => "SQL_BigInt",
    "FLOAT"            => "SQL_Float",
    "REAL"             => "SQL_Double_Float",
    "DOUBLE"           => "SQL_Double_Float",
    "DOUBLE PRECISION" => "SQL_Double_Float",
    "DEC"              => "SQL_Decimal",
    "FIXED"            => "SQL_Decimal",
    "NUMERIC"          => "SQL_Decimal",
    "DECIMAL"          => "SQL_Decimal",
    "CHAR"             => "SQL_Char",
    "BINARY"           => "SQL_Binary",
    "VARCHAR"          => "SQL_VarChar",
    "VARBINARY"        => "SQL_VarBinary",
    "TINYTEXT"         => "SQL_Text",
    "TINYBLOB"         => "SQL_Blob",
    "TEXT"             => "SQL_Text",
    "BLOB"             => "SQL_Blob",
    "MEDIUMTEXT"       => "SQL_Text",
    "MEDIUMBLOB"       => "SQL_Blob",
    "LONGTEXT"         => "SQL_Text",
    "LONGBLOB"         => "SQL_Blob",
    "ENUM"             => "SQL_Enum",
    "SET"              => "SQL_Set",
    "DATE"             => "SQL_Date",
    "DATETIME"         => "SQL_Datetime",
    "TIMESTAMP"        => "SQL_Timestamp",
    "TIME"             => "SQL_Time",
    "YEAR"             => "SQL_Year",
    "GEOMETRY"         => "SQL_Geometry",
    );


plan( 2 + count($sql_types) );

require_ok("SQL/Types.php");

foreach ($sql_types as $sql_type=>$class) {
    $type = SQL_Type::create($sql_type);
    is( get_class($type), $class, "Create $sql_type -> $class" );
}

try {
    $type = SQL_Type::create("BOGUS");
    fail( "Creating a bogus type throws exception" );
    diag( "Got: ".get_class($type)." expected: Exception");
}
catch (Exception $e) {
    is( $e->getMessage(), "Unknown SQL type: BOGUS", "Creating a bogus type throws exception" );
}

$bit = SQL_Type::create("BIT");
is( $bit->name, "BIT", "Name property is set");
is( $bit->toSql(), "BIT", "SQL name is correct");

$num = SQL_Type::create("INTEGER");
ok( ! $bit->equalTo($num), "Bits are not integers" );
ok( $bit->equalTo($bit), "Bits are indeed bits" );
ok( $bit->isValid(), "Bits are valid" );

$int = SQL_Type::create("INTEGER");
is( $int->length, $int->default_length, "Length is initialized properly" );
