<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing a type declaration
 */
class SQL_Type {
    static function create($type) {
        switch ($type) {
            case "BIT":
                return new SQL_Bit($type);
            case "BOOL":
            case "BOOLEAN":
            case "TINYINT":
                return new SQL_TinyInt($type);
            case "SMALLINT":
                return new SQL_SmallInt($type);
            case "MEDIUMINT":
                return new SQL_MediumInt($type);
            case "INT":
            case "INTEGER":
                return new SQL_Integer($type);
            case "BIGINT":
                return new SQL_BigInt($type);
            case "SERIAL":
                $new = new SQL_BigInt($type);
                $new->unsigned = TRUE;
                return $new;
            case "FLOAT":
                return new SQL_Float($type);
            case "REAL":
            case "DOUBLE":
            case "DOUBLE PRECISION":
                return new SQL_Double_Float($type);
            case "DEC":
            case "FIXED":
            case "NUMERIC":
            case "DECIMAL":
                return new SQL_Decimal($type);
            case "CHAR":
                return new SQL_Char($type);
            case "BINARY":
                return new SQL_Binary($type);
            case "VARCHAR":
                return new SQL_Varchar($type);
            case "VARBINARY":
                return new SQL_Varbinary($type);
            case "TINYTEXT":
                return new SQL_Text($type,255); // 2^8 -1
            case "TINYBLOB":
                return new SQL_Blob($type,255); // 2^8 -1
            case "TEXT":
                return new SQL_Text($type,65535); // 2^16 -1
            case "BLOB":
                return new SQL_Blob($type,65535); // 2^16 -1
            case "MEDIUMTEXT":
                return new SQL_Text($type,16777215); // 2^24 -1
            case "MEDIUMBLOB":
                return new SQL_Blob($type,16777215); // 2^24 -1
            case "LONGTEXT":
                return new SQL_Text($type,4294967295); // 2^32 -1
            case "LONGBLOB":
                return new SQL_Blob($type,4294967295); // 2^32 -1
            case "ENUM":
                return new SQL_Enum($type);
            case "SET":
                return new SQL_Set($type);
            case "DATE":
                return new SQL_Date($type);
            case "DATETIME":
                return new SQL_Datetime($type);
            case "TIMESTAMP":
                return new SQL_Timestamp($type);
            case "TIME":
                return new SQL_Time($type);
            case "YEAR":
                return new SQL_Year($type);
            case "GEOMETRY":
                return new SQL_Geometry($type);
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
        if ( get_class($this) != get_class($other) ) { return FALSE; }
        return TRUE;
    }
    function cloneFrom($new) {}
    function normalize($value) {
        return $value->value();
    }
    function isValid() {
        return true;
    }
}

class SQL_Bit extends SQL_Type {}

class SQL_Numeric extends SQL_Type {
    public $default_length = 11;
    public $length;
    public $unsigned = FALSE;
    public $zerofill = FALSE;

    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return FALSE; }
        if ( $this->unsigned != $other->unsigned ) { return FALSE; }
        if ( $this->zerofill != $other->zerofill ) { return FALSE; }
        if ( $this->length != $other->length) { return FALSE; }
        return TRUE;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->unsigned = $old->unsigned;
        $this->zerofill = $old->zerofill;
        $this->length = $old->length;
    }
    function numify($value) {
        if ( $value instanceOf SQL_Token_String ) {
            $plain = $value->unquote() + 0;
        }
        else if ( $value instanceOf SQL_Token_Num ) {
            $plain = $value->value() + 0;
        }
        else {
            $plain = 0;
        }
        return $plain;
    }
}

class SQL_Integer extends SQL_Numeric {
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
        if ( $int instanceOf SQL_Token_Reserved ) {
            return $int->value();
        }
        return round($this->numify($int));
    }
}

class SQL_TinyInt extends SQL_Integer {
    public $default_length = 4;
}

class SQL_SmallInt extends SQL_Integer {
    public $default_length = 6;
}

class SQL_MediumInt extends SQL_Integer {
    public $default_length = 9;
}

class SQL_BigInt extends SQL_Integer {
    public $default_length = 20;
}

class SQL_Decimal extends SQL_Numeric {
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
        if ( ! parent::equalTo($other) ) { return FALSE; }
        if ( $this->scale != $other->scale) { return FALSE; }
        return TRUE;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->scale = $old->scale;
    }
    function normalize($num) {
        if ( $num instanceOf SQL_Token_Reserved ) {
            return $num->value();
        }
        return $this->numify($num);
    }
}

class SQL_Float extends SQL_Numeric {
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
        if ( get_class($this) != get_class($other) ) { return FALSE; }
        if ( $this->unsigned != $other->unsigned ) { return FALSE; }
        if ( $this->zerofill != $other->zerofill ) { return FALSE; }
        if ( $this->decimals != $other->decimals ) { return FALSE; }
        if ( $this->decimals ) {
            if ( $this->length != $other->length) { return FALSE; }
        }
        return TRUE;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->decimals = $old->decimals;
    }
    function normalize($float) {
        if ( $float instanceOf SQL_Token_Reserved ) {
            return $float->value();
        }
        return $this->numify($float);
    }
}

class SQL_Double_Float extends SQL_Float {}

class SQL_Date extends SQL_Type {
    function normalize($date) {
        if ( $date instanceOf SQL_Token_Reserved ) {
            return $date->value();
        }
        if ( $date instanceOf SQL_Token_Num ) {
            if ( $date->value() == 0 ) {
                return "'0000-00-00'";
            }
            else {
                throw new Exception("Invalid default for date: ".$date->debug());
            }
        }
        if ( ! $date instanceOf SQL_Token_String ) {
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
class SQL_Datetime extends SQL_Type {
    function normalize($date) {
        if ( $date instanceOf SQL_Token_Reserved ) {
            return $date->value();
        }
        if ( $date instanceOf SQL_Token_Num ) {
            if ( $date->value() == 0 ) {
                return "'0000-00-00 00:00:00'";
            }
            else {
                throw new Exception("Invalid default for date: ".$date->debug());
            }
        }
        if ( ! $date instanceOf SQL_Token_String ) {
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
class SQL_Time extends SQL_Type {}
class SQL_Timestamp extends SQL_Type {}
class SQL_Year extends SQL_Type {
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
        if ( ! parent::equalTo($other) ) { return FALSE; }
        if ( $this->length != $other->length ) { return FALSE; }
        return TRUE;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->length = $old->length;
    }
    function normalize($year) {
        if ( $year instanceOf SQL_Token_Reserved ) {
            return $year->value();
        }
        if ( $year instanceOf SQL_Token_String ) {
            $plain = $year->unquote() + 0;
            if ( $plain >= 0 and $plain < 70 ) {
                return "'20$plain'";
            }
            else if ( $plain >= 70 and $plain < 100 ) {
                return "'19$plain'";
            }
        }
        else if ( $year instanceOf SQL_Token_Num ) {
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

class SQL_String extends SQL_Type {
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
        if ( ! parent::equalTo($other) ) { return FALSE; }
        if ( $this->charset() != $other->charset() ) { return FALSE; }
        if ( $this->collate() != $other->collate() ) { return FALSE; }
        return TRUE;
    }
    function cloneFrom($old) {
        parent::cloneFrom($old);
        $this->charset( $old->charset() );
        $this->collate( $old->collate() );
    }
    
    
    function normalize($str) {
        if ( $str instanceOf SQL_Token_Reserved ) {
            return $str->value();
        }
        else if ( $str instanceOf SQL_Token_String ) {
            $value = $str->unquote();
        }
        else if ( $str instanceOf SQL_Token_Num ) {
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
        return SQL::quote_str( $value );
    }
    function charset_collation($other=null) {
        $other_charset = isset($other)? $other->charset(): $this->default_charset;
        $other_collate = isset($other)? $other->collate(): $this->default_collate;
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

class SQL_VarString extends SQL_String {
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return FALSE; }
        if ( $this->length != $other->length ) { return FALSE; }
        return TRUE;
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

class SQL_VarChar extends SQL_VarString {
    function binary() {
        $new = new SQL_VarBinary("VARBINARY");
        $new->cloneFrom($this);
        return $new;
    }
}
class SQL_VarBinary extends SQL_VarString {}

class SQL_Char extends SQL_VarString {
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
        $new = new SQL_Binary("BINARY");
        $new->cloneFrom($this);
        return $new;
    }
}
class SQL_Binary extends SQL_Char { }

class SQL_Text extends SQL_String {
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
        $new = new SQL_Blob("BLOB");
        $new->cloneFrom($this);
        return $new;
    }
}
class SQL_Blob extends SQL_Text {
    function type_name($size) { return $size . "BLOB"; }
}

class SQL_Geometry extends SQL_String { }

class SQL_Compound extends SQL_String {
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

class SQL_Enum extends SQL_Compound { }

class SQL_Set extends SQL_Compound { }
