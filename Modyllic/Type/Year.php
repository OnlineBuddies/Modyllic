<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Year extends Modyllic_Type {
    public $default_length = 4;
    public $length;
    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }

    function to_sql() {
        $sql = $this->name;
        if ( $this->length != $this->default_length ) {
            $sql .= '(' . $this->length . ')';
        }
        return $sql;
    }
    function equal_to(Modyllic_Type $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->length != $other->length ) { return false; }
        return true;
    }
    function copy_from(Modyllic_Type $old) {
        parent::copy_from($old);
        $this->length = $old->length;
    }
    function normalize($year) {
        $is_object = is_object($year);
        $value = $is_object ? $year->value() : $year;
        $unquoted = $is_object ? $year->unquote() : $year;
        if ( $year instanceOf Modyllic_Token_Reserved or (!$is_object and Modyllic_SQL::is_reserved($value) ) ) {
            return $value;
        }
        if ( $year instanceOf Modyllic_Token_Num or (!$is_object and is_numeric($year)) ) {
            $plain = $value + 0;
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
        else if ( !$is_object or $year instanceOf Modyllic_Token_String ) {
            $plain = $unquoted + 0;
            if ( $plain >= 0 and $plain < 70 ) {
                return "'20$plain'";
            }
            else if ( $plain >= 70 and $plain < 100 ) {
                return "'19$plain'";
            }
        }
        throw new Exception( "Expected a valid year, got: $year" );
    }
}
