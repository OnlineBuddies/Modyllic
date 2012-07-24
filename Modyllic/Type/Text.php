<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Text extends Modyllic_Type_String {
    function __construct($type,$length) {
        parent::__construct($type);
        $this->length = $length;
    }
    function clone_from(Modyllic_Type $old) {
        parent::clone_from($old);
        $this->length = $old->length;
    }
    function type_name($size) { return $size . "TEXT"; }
    function to_sql(Modyllic_Type $other=null) {
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
    function make_binary() {
        $new = new Modyllic_Type_Blob("BLOB");
        $new->clone_from($this);
        return $new;
    }
}
