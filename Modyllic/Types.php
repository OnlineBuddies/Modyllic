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
class Modyllic_Type {
    static function create($type) {
        switch ($type) {
            case "BIT":
                return new Modyllic_Bit($type);
            case "BOOL":
            case "BOOLEAN":
                return new Modyllic_Boolean($type);
            case "TINYINT":
                return new Modyllic_TinyInt($type);
            case "SMALLINT":
                return new Modyllic_SmallInt($type);
            case "MEDIUMINT":
                return new Modyllic_MediumInt($type);
            case "INT":
            case "INTEGER":
                return new Modyllic_Integer($type);
            case "BIGINT":
                return new Modyllic_BigInt($type);
            case "SERIAL":
                $new = new Modyllic_BigInt($type);
                $new->unsigned = true;
                return $new;
            case "FLOAT":
                return new Modyllic_Float($type);
            case "REAL":
            case "DOUBLE":
            case "DOUBLE PRECISION":
                return new Modyllic_Double_Float($type);
            case "DEC":
            case "FIXED":
            case "NUMERIC":
            case "DECIMAL":
                return new Modyllic_Decimal($type);
            case "CHAR":
                return new Modyllic_Char($type);
            case "BINARY":
                return new Modyllic_Binary($type);
            case "VARCHAR":
                return new Modyllic_VarChar($type);
            case "VARBINARY":
                return new Modyllic_VarBinary($type);
            case "TINYTEXT":
                return new Modyllic_Text($type,255); // 2^8 -1
            case "TINYBLOB":
                return new Modyllic_Blob($type,255); // 2^8 -1
            case "TEXT":
                return new Modyllic_Text($type,65535); // 2^16 -1
            case "BLOB":
                return new Modyllic_Blob($type,65535); // 2^16 -1
            case "MEDIUMTEXT":
                return new Modyllic_Text($type,16777215); // 2^24 -1
            case "MEDIUMBLOB":
                return new Modyllic_Blob($type,16777215); // 2^24 -1
            case "LONGTEXT":
                return new Modyllic_Text($type,4294967295); // 2^32 -1
            case "LONGBLOB":
                return new Modyllic_Blob($type,4294967295); // 2^32 -1
            case "ENUM":
                return new Modyllic_Enum($type);
            case "SET":
                return new Modyllic_Set($type);
            case "DATE":
                return new Modyllic_Date($type);
            case "DATETIME":
                return new Modyllic_Datetime($type);
            case "TIMESTAMP":
                return new Modyllic_Timestamp($type);
            case "TIME":
                return new Modyllic_Time($type);
            case "YEAR":
                return new Modyllic_Year($type);
            case "GEOMETRY":
                return new Modyllic_Geometry($type);
            default:
                throw new Exception("Unknown SQL type: $type" );
        }
    }

    public $name;
    function __construct($type) {
        $this->name = $type;
    }
    function toSql() {
        return $this->name;
    }
    function equalTo($other) {
        if ( !$this->isaEquivalent($other) and !$other->isaEquivalent($this) ) { return false; }
        return true;
    }
    function isaEquivalent($other) {
        return get_class($this) == get_class($other);
    }
    function cloneFrom($new) {}
    function normalize($value) {
        return $value->value();
    }
    function isValid() {
        return true;
    }
}

class Modyllic_Bit extends Modyllic_Type {}

class Modyllic_Numeric extends Modyllic_Type {
    public $default_length = 11;
    public $length;
    public $unsigned = false;
    public $zerofill = false;

    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->unsigned != $other->unsigned ) { return false; }
        if ( $this->zerofill != $other->zerofill ) { return false; }
        if ( $this->length != $other->length) { return false; }
        return true;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->unsigned = $old->unsigned;
        $this->zerofill = $old->zerofill;
        $this->length = $old->length;
    }
    function numify($value) {
        if ( $value instanceOf Modyllic_Token_String ) {
            $plain = $value->unquote() + 0;
        }
        else if ( $value instanceOf Modyllic_Token_Num ) {
            $plain = $value->value() + 0;
        }
        else {
            $plain = 0;
        }
        return $plain;
    }
}

class Modyllic_Integer extends Modyllic_Numeric {
    function toSql() {
        $sql = $this->name;
        if ( $this->length != $this->default_length ) {
            $sql .= '(' . $this->length . ')';
        }
        if ( $this->unsigned ) {
            $sql .= ' UNSIGNED';
        }
        if ( $this->zerofill ) {
            $sql .= ' ZEROFILL';
        }
        return $sql;
    }
    function normalize($int) {
        if ( $int instanceOf Modyllic_Token_Reserved ) {
            return $int->value();
        }
        return round($this->numify($int));
    }
}

class Modyllic_TinyInt extends Modyllic_Integer {
    public $default_length = 4;
}

class Modyllic_Boolean extends Modyllic_TinyInt {
    public $default_length = 1;
    function isaEquivalent($other) {
        if ( parent::isaEquivalent($other) ) { return true; }
        if ( get_class($other) == "Modyllic_TinyInt" ) { return true; }
        return false;
    }
}

class Modyllic_SmallInt extends Modyllic_Integer {
    public $default_length = 6;
}

class Modyllic_MediumInt extends Modyllic_Integer {
    public $default_length = 9;
}

class Modyllic_BigInt extends Modyllic_Integer {
    public $default_length = 20;
}

class Modyllic_Decimal extends Modyllic_Numeric {
    public $default_length = 10;
    public $default_scale  = 0;
    public $scale;
    function __construct($type) {
        parent::__construct($type);
        $this->scale = $this->default_scale;
    }

    function toSql() {
        $sql = $this->name;
        if ( $this->length != $this->default_length  or $this->scale != $this->default_scale ) {
            $sql .= '(' . $this->length . ',' . $this->scale . ')';
        }
        if ( $this->unsigned ) {
            $sql .= ' UNSIGNED';
        }
        if ( $this->zerofill ) {
            $sql .= ' ZEROFILL';
        }
        return $sql;
    }
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->scale != $other->scale) { return false; }
        return true;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->scale = $old->scale;
    }
    function normalize($num) {
        if ( $num instanceOf Modyllic_Token_Reserved ) {
            return $num->value();
        }
        return $this->numify($num);
    }
}

class Modyllic_Float extends Modyllic_Numeric {
    public $decimals;
    function toSql() {
        $sql = $this->name;
        if ( $this->decimals ) {
            $sql .= '(' . $this->length . ',' . $this->decimals . ')';
        }
        if ( $this->unsigned ) {
            $sql .= ' UNSIGNED';
        }
        if ( $this->zerofill ) {
            $sql .= ' ZEROFILL';
        }
        return $sql;
    }
    function equalTo($other) {
        if ( get_class($this) != get_class($other) ) { return false; }
        if ( $this->unsigned != $other->unsigned ) { return false; }
        if ( $this->zerofill != $other->zerofill ) { return false; }
        if ( $this->decimals != $other->decimals ) { return false; }
        if ( $this->decimals ) {
            if ( $this->length != $other->length) { return false; }
        }
        return true;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->decimals = $old->decimals;
    }
    function normalize($float) {
        if ( $float instanceOf Modyllic_Token_Reserved ) {
            return $float->value();
        }
        return $this->numify($float);
    }
}

class Modyllic_Double_Float extends Modyllic_Float {}

class Modyllic_Date extends Modyllic_Type {
    function normalize($date) {
        if ( $date instanceOf Modyllic_Token_Reserved ) {
            return $date->value();
        }
        if ( $date instanceOf Modyllic_Token_Num ) {
            if ( $date->value() == 0 ) {
                return "'0000-00-00'";
            }
            else {
                throw new Exception("Invalid default for date: ".$date->debug());
            }
        }
        if ( ! $date instanceOf Modyllic_Token_String ) {
            throw new Exception("Invalid default for date: ".$date->debug());
        }
        if ( $date->value() == '0' ) {
            return "'0000-00-00'";
        }
        if ( ! preg_match( '/^\d\d\d\d-\d\d-\d\d$/', $date->unquote() ) ) {
            throw new Exception("Invalid default for date: ".$date->debug());
        }
        return $date->value();
    }
}
class Modyllic_Datetime extends Modyllic_Type {
    function normalize($date) {
        if ( $date instanceOf Modyllic_Token_Reserved ) {
            return $date->value();
        }
        if ( $date instanceOf Modyllic_Token_Num ) {
            if ( $date->value() == 0 ) {
                return "'0000-00-00 00:00:00'";
            }
            else {
                throw new Exception("Invalid default for date: ".$date->debug());
            }
        }
        if ( ! $date instanceOf Modyllic_Token_String ) {
            throw new Exception("Invalid default for date: ".$date->debug());
        }
        if ( $date->value() == '0' ) {
            return "'0000-00-00 00:00:00'";
        }
        if ( preg_match( '/^(\d{1,4})-(\d\d?)-(\d\d?)(?: (\d\d?)(?::(\d\d?)(?::(\d\d?))?)?)?$/', $date->unquote(), $matches ) ) {
            $year = $matches[1];
            $mon  = $matches[2];
            $day  = $matches[3];
            $hour = isset($matches[4])? $matches[4] : 0;
            $min  = isset($matches[5])? $matches[5] : 0;
            $sec  = isset($matches[6])? $matches[6] : 0;
            #list( $full, $year, $mon, $day, $hour, $min, $sec ) = $matches;
            return sprintf("'%04d-%02d-%02d %02d:%02d:%02d'", $year, $mon, $day, $hour, $min, $sec );
        }
        else {
            throw new Exception("Invalid default for date: ".$date->debug());
        }
    }
}
class Modyllic_Time extends Modyllic_Type {}
class Modyllic_Timestamp extends Modyllic_Type {}
class Modyllic_Year extends Modyllic_Type {
    public $default_length = 4;
    public $length;
    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }

    function toSql() {
        $sql = $this->name;
        if ( $this->length != $this->default_length ) {
            $sql .= '(' . $this->length . ')';
        }
        return $sql;
    }
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->length != $other->length ) { return false; }
        return true;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->length = $old->length;
    }
    function normalize($year) {
        if ( $year instanceOf Modyllic_Token_Reserved ) {
            return $year->value();
        }
        if ( $year instanceOf Modyllic_Token_String ) {
            $plain = $year->unquote() + 0;
            if ( $plain >= 0 and $plain < 70 ) {
                return "'20$plain'";
            }
            else if ( $plain >= 70 and $plain < 100 ) {
                return "'19$plain'";
            }
        }
        else if ( $year instanceOf Modyllic_Token_Num ) {
            $plain = $year->value() + 0;
            if ( $plain == 0 ) {
                return "'0000'";
            }
            else if ( $plain > 0 and $plain < 70 ) {
                return "'20$plain'";
            }
            else if ( $plain >= 70 and $plain < 100 ) {
                return "'19$plain'";
            }
            else if ( $plain > 1900 and $plain < 2155 ) {
                return "'$plain'";
            }
        }
        throw new Exception( "Expected a valid year, got: ".$year->debug() );
    }
}

class Modyllic_String extends Modyllic_Type {
    protected $default_charset = "utf8";
    protected $default_collate = "utf8_general_ci";
    private $charset;
    private $collate;
    public $length;

    function set_default_charset($value) {
        $this->default_charset = $value;
    }
    function set_default_collate($value) {
        $this->default_collate = $value;
    }

    function charset($value=null) {
        $args = func_num_args();
        if ( $args ) {
            $this->charset = $value;
        }
        else {
            return isset($this->charset) ? $this->charset : $this->default_charset;
        }
    }

    function collate($value=null) {
        $args = func_num_args();
        if ( $args ) {
            $this->collate = $value;
        }
        else {
            return isset($this->collate) ? $this->collate : $this->default_collate;
        }
    }

    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->charset() != $other->charset() ) { return false; }
        if ( $this->collate() != $other->collate() ) { return false; }
        return true;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->charset( $old->charset() );
        $this->collate( $old->collate() );
    }


    function normalize($str) {
        if ( $str instanceOf Modyllic_Token_Reserved ) {
            return $str->value();
        }
        else if ( $str instanceOf Modyllic_Token_String ) {
            $value = $str->unquote();
        }
        else if ( $str instanceOf Modyllic_Token_Num ) {
            $value = $str->value();
        }
        else if ( ! is_object($str) ) {
            $value = $str;
        }
        else {
            throw new Exception( "Expected a valid string, got: ".$str->debug() );
        }
        if ( isset($this->length) ) {
            $value = substr( $value, 0, $this->length );
        }
        return Modyllic_SQL::quote_str( $value );
    }
    function charset_collation($other=null) {
        $other_charset = $other instanceOf Modyllic_String? $other->charset(): $this->default_charset;
        $other_collate = $other instanceOf Modyllic_String? $other->collate(): $this->default_collate;
        $diff_charset = $this->charset() != $other_charset;
        $diff_collate = $this->collate() != $other_collate;
        if ( $diff_charset or $diff_collate ) {
            if ( $this->charset() == "latin1" and $this->collate() == "latin1_general_ci" ) {
                return " ASCII";
            }
            if ( $this->charset() == "ucs2" and $this->collate() == "ucs2_general_ci" ) {
                return " UNICODE";
            }
        }
        $sql = "";
        if ( $diff_charset ) {
            $sql .= " CHARACTER SET ".$this->charset();
        }
        if ( $diff_collate ) {
            if ( preg_match('/_bin$/', $this->collate() ) ) {
                $sql .= " BINARY";
            }
            else {
                $sql .= " COLLATE ".$this->collate();
            }
        }
        return $sql;
    }
}

class Modyllic_VarString extends Modyllic_String {
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->length != $other->length ) { return false; }
        return true;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->length = $old->length;
    }
    function toSql($other=null) {
        $sql = $this->name . "(".$this->length.")";
        $sql .= $this->charset_collation($other);
        return $sql;
    }
    function isValid() {
        return isset($this->length) and parent::isValid();
    }
}

class Modyllic_VarChar extends Modyllic_VarString {
    function binary() {
        $new = new Modyllic_VarBinary("VARBINARY");
        $new->cloneFrom($this);
        return $new;
    }
}
class Modyllic_VarBinary extends Modyllic_VarString {}

class Modyllic_Char extends Modyllic_VarString {
    public $default_length = 1;
    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }
    function toSql($other=null) {
        $sql = $this->name;
        if ( $this->length != $this->default_length ) {
            $sql .= "(".$this->length.")";
        }
        $sql .= $this->charset_collation($other);
        return $sql;
    }
    function binary() {
        $new = new Modyllic_Binary("BINARY");
        $new->cloneFrom($this);
        return $new;
    }
}
class Modyllic_Binary extends Modyllic_Char { }

class Modyllic_Text extends Modyllic_String {
    function __construct($type,$length) {
        parent::__construct($type);
        $this->length = $length;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->length = $old->length;
    }
    function type_name($size) { return $size . "TEXT"; }
    function toSql($other=null) {
        if ( $this->length < 256 ) { // 2^8
            $sql = $this->type_name("TINY");
        }
        else if ( $this->length < 65536 ) { // 2^16
            $sql = $this->type_name("");
        }
        else if ( $this->length < 16777216 ) { // 2^24
            $sql = $this->type_name("MEDIUM");
        }
        else {
            $sql = $this->type_name("LONG");
        }
        $sql .= $this->charset_collation($other);
        return $sql;
    }
    function binary() {
        $new = new Modyllic_Blob("BLOB");
        $new->cloneFrom($this);
        return $new;
    }
}
class Modyllic_Blob extends Modyllic_Text {
    function type_name($size) { return $size . "BLOB"; }
}

class Modyllic_Geometry extends Modyllic_String { }

class Modyllic_Compound extends Modyllic_String {
    public $values;
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->values = $old->values;
    }
    function toSql($other=null) {
        $sql = $this->name . "(";
        $valuec = 0;
        foreach ( $this->values as $value ) {
            if ( $valuec ++ ) {
                $sql .= ",";
            }
            $sql .= $value;
        }
        $sql .= ")";
        $sql .= $this->charset_collation($other);
        return $sql;
    }
}

class Modyllic_Enum extends Modyllic_Compound { }

class Modyllic_Set extends Modyllic_Compound { }
