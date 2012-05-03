<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing a type declaration
 */
abstract class Modyllic_Type {
    static function create($type) {
        switch ($type) {
            case "BIT":
                return new Modyllic_Type_Bit($type);
            case "BOOL":
            case "BOOLEAN":
                return new Modyllic_Type_Boolean($type);
            case "TINYINT":
                return new Modyllic_Type_TinyInt($type);
            case "SMALLINT":
                return new Modyllic_Type_SmallInt($type);
            case "MEDIUMINT":
                return new Modyllic_Type_MediumInt($type);
            case "INT":
            case "INTEGER":
                return new Modyllic_Type_Integer($type);
            case "BIGINT":
                return new Modyllic_Type_BigInt($type);
            case "SERIAL":
                $new = new Modyllic_Type_BigInt($type);
                $new->unsigned = true;
                return $new;
            case "FLOAT":
                return new Modyllic_Type_Float($type);
            case "REAL":
            case "DOUBLE":
            case "DOUBLE PRECISION":
                return new Modyllic_Type_DoubleFloat($type);
            case "DEC":
            case "FIXED":
            case "NUMERIC":
            case "DECIMAL":
                return new Modyllic_Type_Decimal($type);
            case "CHAR":
                return new Modyllic_Type_Char($type);
            case "BINARY":
                return new Modyllic_Type_Binary($type);
            case "VARCHAR":
                return new Modyllic_Type_VarChar($type);
            case "VARBINARY":
                return new Modyllic_Type_VarBinary($type);
            case "TINYTEXT":
                return new Modyllic_Type_Text($type,255); // 2^8 -1
            case "TINYBLOB":
                return new Modyllic_Type_Blob($type,255); // 2^8 -1
            case "TEXT":
                return new Modyllic_Type_Text($type,65535); // 2^16 -1
            case "BLOB":
                return new Modyllic_Type_Blob($type,65535); // 2^16 -1
            case "MEDIUMTEXT":
                return new Modyllic_Type_Text($type,16777215); // 2^24 -1
            case "MEDIUMBLOB":
                return new Modyllic_Type_Blob($type,16777215); // 2^24 -1
            case "LONGTEXT":
                return new Modyllic_Type_Text($type,4294967295); // 2^32 -1
            case "LONGBLOB":
                return new Modyllic_Type_Blob($type,4294967295); // 2^32 -1
            case "ENUM":
                return new Modyllic_Type_Enum($type);
            case "SET":
                return new Modyllic_Type_Set($type);
            case "DATE":
                return new Modyllic_Type_Date($type);
            case "DATETIME":
                return new Modyllic_Type_Datetime($type);
            case "TIMESTAMP":
                return new Modyllic_Type_Timestamp($type);
            case "TIME":
                return new Modyllic_Type_Time($type);
            case "YEAR":
                return new Modyllic_Type_Year($type);
            case "GEOMETRY":
                return new Modyllic_Type_Geometry($type);
            default:
                throw new Exception("Unknown SQL type: $type" );
        }
    }

    public $name;
    function __construct($type) {
        $this->name = $type;
    }
    function to_sql() {
        return $this->name;
    }
    function equal_to(Modyllic_Type $other) {
        if ( !$this->isa_equivalent($other) and !$other->isa_equivalent($this) ) { return false; }
        return true;
    }
    function isa_equivalent(Modyllic_Type $other) {
        return get_class($this) == get_class($other);
    }
    function clone_from(Modyllic_Type $new) {}
    function normalize(Modyllic_Token $value) {
        return $value->value();
    }
    function is_valid() {
        return true;
    }
}

require_once "Modyllic/Type/Numeric.php";
require_once "Modyllic/Type/Integer.php";
require_once "Modyllic/Type/String.php";
require_once "Modyllic/Type/VarString.php";
require_once "Modyllic/Type/Char.php";
require_once "Modyllic/Type/Text.php";
require_once "Modyllic/Type/TinyInt.php";
require_once "Modyllic/Type/Float.php";

require_once "Modyllic/Type/BigInt.php";
require_once "Modyllic/Type/Binary.php";
require_once "Modyllic/Type/Bit.php";
require_once "Modyllic/Type/Blob.php";
require_once "Modyllic/Type/Boolean.php";
require_once "Modyllic/Type/Compound.php";
require_once "Modyllic/Type/Date.php";
require_once "Modyllic/Type/Datetime.php";
require_once "Modyllic/Type/Decimal.php";
require_once "Modyllic/Type/DoubleFloat.php";
require_once "Modyllic/Type/Enum.php";
require_once "Modyllic/Type/Geometry.php";
require_once "Modyllic/Type/MediumInt.php";
require_once "Modyllic/Type/Set.php";
require_once "Modyllic/Type/SmallInt.php";
require_once "Modyllic/Type/Time.php";
require_once "Modyllic/Type/Timestamp.php";
require_once "Modyllic/Type/VarBinary.php";
require_once "Modyllic/Type/VarChar.php";
require_once "Modyllic/Type/Year.php";
