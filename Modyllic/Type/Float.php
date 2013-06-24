<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Float extends Modyllic_Type_Numeric {
    public $decimals;
    function to_sql() {
        $sql = $this->name;
        if ( $this->decimals ) {
            $sql .= '(' . $this->length() . ',' . $this->decimals . ')';
        }
        if ( $this->unsigned ) {
            $sql .= ' UNSIGNED';
        }
        if ( $this->zerofill ) {
            $sql .= ' ZEROFILL';
        }
        return $sql;
    }
    function equal_to(Modyllic_Type $other) {
        if ( get_class($this) != get_class($other) ) { return false; }
        if ( $this->unsigned != $other->unsigned ) { return false; }
        if ( $this->zerofill != $other->zerofill ) { return false; }
        if ( $this->decimals != $other->decimals ) { return false; }
        if ( $this->decimals ) {
            if ( $this->length() != $other->length()) { return false; }
        }
        return true;
    }
    function copy_from(Modyllic_Type $old) {
        parent::copy_from($old);
        $this->decimals = $old->decimals;
    }
    function normalize($float) {
        if ( $float instanceOf Modyllic_Token_Bareword and Modyllic_SQL::is_reserved($float->token()) ) {
            return $float->value();
        }
        return $this->numify($float);
    }
}

