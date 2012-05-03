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
    "BIT"              => "Modyllic_Type_Bit",
    "BOOL"             => "Modyllic_Type_Boolean",
    "BOOLEAN"          => "Modyllic_Type_Boolean",
    "TINYINT"          => "Modyllic_Type_TinyInt",
    "SMALLINT"         => "Modyllic_Type_SmallInt",
    "MEDIUMINT"        => "Modyllic_Type_MediumInt",
    "INT"              => "Modyllic_Type_Integer",
    "INTEGER"          => "Modyllic_Type_Integer",
    "BIGINT"           => "Modyllic_Type_BigInt",
    "SERIAL"           => "Modyllic_Type_BigInt",
    "FLOAT"            => "Modyllic_Type_Float",
    "REAL"             => "Modyllic_Type_DoubleFloat",
    "DOUBLE"           => "Modyllic_Type_DoubleFloat",
    "DOUBLE PRECISION" => "Modyllic_Type_DoubleFloat",
    "DEC"              => "Modyllic_Type_Decimal",
    "FIXED"            => "Modyllic_Type_Decimal",
    "NUMERIC"          => "Modyllic_Type_Decimal",
    "DECIMAL"          => "Modyllic_Type_Decimal",
    "CHAR"             => "Modyllic_Type_Char",
    "BINARY"           => "Modyllic_Type_Binary",
    "VARCHAR"          => "Modyllic_Type_VarChar",
    "VARBINARY"        => "Modyllic_Type_VarBinary",
    "TINYTEXT"         => "Modyllic_Type_Text",
    "TINYBLOB"         => "Modyllic_Type_Blob",
    "TEXT"             => "Modyllic_Type_Text",
    "BLOB"             => "Modyllic_Type_Blob",
    "MEDIUMTEXT"       => "Modyllic_Type_Text",
    "MEDIUMBLOB"       => "Modyllic_Type_Blob",
    "LONGTEXT"         => "Modyllic_Type_Text",
    "LONGBLOB"         => "Modyllic_Type_Blob",
    "ENUM"             => "Modyllic_Type_Enum",
    "SET"              => "Modyllic_Type_Set",
    "DATE"             => "Modyllic_Type_Date",
    "DATETIME"         => "Modyllic_Type_Datetime",
    "TIMESTAMP"        => "Modyllic_Type_Timestamp",
    "TIME"             => "Modyllic_Type_Time",
    "YEAR"             => "Modyllic_Type_Year",
    "GEOMETRY"         => "Modyllic_Type_Geometry",
    );


plan( 15 + count($sql_types) );

require_ok("Modyllic/Type.php");

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

$bool = Modyllic_Type::create("BOOLEAN");
is( $bool->length, 1, "Boolean always have a length of 1");
is( $bool->to_sql(), "BOOLEAN", "Boolean types properly become themselves");
$tinyint = Modyllic_Type::create("TINYINT");
ok( $bool->isa_equivalent($tinyint), "BOOLEAN as a type is equivalent of TINYINT");
ok( !$bool->equal_to($tinyint), "A full type declaration of BOOLEAN is not the same as just TINYINT");
ok( !$tinyint->equal_to($bool), "A full type declaration of just TINYINT is not the same as BOOLEAN");
$tinyint->length = 1;
ok( $bool->equal_to($tinyint), "A full type declaration of BOOLEAN is the same as TINYINT(1)");
ok( $tinyint->equal_to($bool), "A full type declaration of TINYINT(1) is the same as BOOLEAN");

$bit = Modyllic_Type::create("BIT");
is( $bit->name, "BIT", "Name property is set");
is( $bit->to_sql(), "BIT", "SQL name is correct");

$num = Modyllic_Type::create("INTEGER");
ok( ! $bit->equal_to($num), "Bits are not integers" );
ok( $bit->equal_to($bit), "Bits are indeed bits" );
ok( $bit->is_valid(), "Bits are valid" );

$int = Modyllic_Type::create("INTEGER");
is( $int->length, $int->default_length, "Length is initialized properly" );
