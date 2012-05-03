<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Integer extends Modyllic_Numeric {
    function to_sql() {
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
    function normalize(Modyllic_Token $int) {
        if ( $int instanceOf Modyllic_Token_Reserved ) {
            return $int->value();
        }
        return round($this->numify($int));
    }
}
