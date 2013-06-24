<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Decimal extends Modyllic_Type_Numeric {
    public $default_length = 10;
    public $default_scale  = 0;
    public $scale;
    function __construct($type) {
        parent::__construct($type);
        $this->scale = $this->default_scale;
    }

    function to_sql() {
        $sql = $this->name;
        if ( $this->length() != $this->default_length()  or $this->scale != $this->default_scale ) {
            $sql .= '(' . $this->length() . ',' . $this->scale . ')';
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
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->scale != $other->scale) { return false; }
        return true;
    }
    function copy_from(Modyllic_Type $old) {
        parent::copy_from($old);
        $this->scale = $old->scale;
    }
    function normalize($num) {
        if ( $num instanceOf Modyllic_Token_Bareword and Modyllic_SQL::is_reserved($num->token()) ) {
            return $num->value();
        }
        return $this->numify($num);
    }
}

