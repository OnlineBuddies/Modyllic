<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Char extends Modyllic_VarString {
    public $default_length = 1;
    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }
    function to_sql(Modyllic_Type $other=null) {
        $sql = $this->name;
        if ( $this->length != $this->default_length ) {
            $sql .= "(".$this->length.")";
        }
        $sql .= $this->charset_collation($other);
        return $sql;
    }
    function binary() {
        $new = new Modyllic_Binary("BINARY");
        $new->clone_from($this);
        return $new;
    }
}
