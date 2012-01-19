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
    "BIT"              => "Modyllic_Bit",
    "BOOL"             => "Modyllic_TinyInt",
    "BOOLEAN"          => "Modyllic_TinyInt",
    "TINYINT"          => "Modyllic_TinyInt",
    "SMALLINT"         => "Modyllic_SmallInt",
    "MEDIUMINT"        => "Modyllic_MediumInt",
    "INT"              => "Modyllic_Integer",
    "INTEGER"          => "Modyllic_Integer",
    "BIGINT"           => "Modyllic_BigInt",
    "SERIAL"           => "Modyllic_BigInt",
    "FLOAT"            => "Modyllic_Float",
    "REAL"             => "Modyllic_Double_Float",
    "DOUBLE"           => "Modyllic_Double_Float",
    "DOUBLE PRECISION" => "Modyllic_Double_Float",
    "DEC"              => "Modyllic_Decimal",
    "FIXED"            => "Modyllic_Decimal",
    "NUMERIC"          => "Modyllic_Decimal",
    "DECIMAL"          => "Modyllic_Decimal",
    "CHAR"             => "Modyllic_Char",
    "BINARY"           => "Modyllic_Binary",
    "VARCHAR"          => "Modyllic_VarChar",
    "VARBINARY"        => "Modyllic_VarBinary",
    "TINYTEXT"         => "Modyllic_Text",
    "TINYBLOB"         => "Modyllic_Blob",
    "TEXT"             => "Modyllic_Text",
    "BLOB"             => "Modyllic_Blob",
    "MEDIUMTEXT"       => "Modyllic_Text",
    "MEDIUMBLOB"       => "Modyllic_Blob",
    "LONGTEXT"         => "Modyllic_Text",
    "LONGBLOB"         => "Modyllic_Blob",
    "ENUM"             => "Modyllic_Enum",
    "SET"              => "Modyllic_Set",
    "DATE"             => "Modyllic_Date",
    "DATETIME"         => "Modyllic_Datetime",
    "TIMESTAMP"        => "Modyllic_Timestamp",
    "TIME"             => "Modyllic_Time",
    "YEAR"             => "Modyllic_Year",
    "GEOMETRY"         => "Modyllic_Geometry",
    );


plan( 2 + count($sql_types) );

require_ok("Modyllic/Types.php");

foreach ($sql_types as $sql_type=>$class) {
    $type = Modyllic_Type::create($sql_type);
    is( get_class($type), $class, "Create $sql_type -> $class" );
}

try {
    $type = Modyllic_Type::create("BOGUS");
    fail( "Creating a bogus type throws exception" );
    diag( "Got: ".get_class($type)." expected: Exception");
}
catch (Exception $e) {
    is( $e->getMessage(), "Unknown SQL type: BOGUS", "Creating a bogus type throws exception" );
}

$bit = Modyllic_Type::create("BIT");
is( $bit->name, "BIT", "Name property is set");
is( $bit->toSql(), "BIT", "SQL name is correct");

$num = Modyllic_Type::create("INTEGER");
ok( ! $bit->equalTo($num), "Bits are not integers" );
ok( $bit->equalTo($bit), "Bits are indeed bits" );
ok( $bit->isValid(), "Bits are valid" );

$int = Modyllic_Type::create("INTEGER");
is( $int->length, $int->default_length, "Length is initialized properly" );
