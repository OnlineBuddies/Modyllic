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
    function clone_from(Modyllic_Type $old) {
        parent::clone_from($old);
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
