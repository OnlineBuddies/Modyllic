<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Integer extends Modyllic_Type_Numeric {
    public $bytes_of_storage = 4;
    function get_range() {
        if ( $this->unsigned ) {
            return array( 0, pow(2, $this->bytes_of_storage * 8) - 1 );
        }
        else {
            $range = pow( 2, ($this->bytes_of_storage * 8)-1);
            return array( -1*$range, $range - 1 );
        }
    }
    function to_sql() {
        $sql = $this->name;
        if ( $this->length() != $this->default_length() ) {
            $sql .= '(' . $this->length() . ')';
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
        if ( $int instanceOf Modyllic_Token_Bareword and Modyllic_SQL::is_reserved($int->token()) ) {
            return $int->value();
        }
        return round($this->numify($int));
    }
}
