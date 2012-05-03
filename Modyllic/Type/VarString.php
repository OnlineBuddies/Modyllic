<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

abstract class Modyllic_Type_VarString extends Modyllic_Type_String {
    function equal_to(Modyllic_Type $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->length != $other->length ) { return false; }
        return true;
    }
    function clone_from(Modyllic_Type $old) {
        parent::clone_from($old);
        $this->length = $old->length;
    }
    function to_sql(Modyllic_Type $other=null) {
        $sql = $this->name . "(".$this->length.")";
        $sql .= $this->charset_collation($other);
        return $sql;
    }
    function is_valid() {
        return isset($this->length) and parent::is_valid();
    }
}
