<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Schema_Index_Foreign extends Modyllic_Schema_Index {
    public $cname = "";
    const WEAK_DEFAULT = false;
    public $weak     = self::WEAK_DEFAULT;
    public $references = array();
    /**
     * @param string $name
     */
    function __construct($name="") {
        parent::__construct($name);
        $this->references['table'] = "";
        $this->references['columns'] = array();
        $this->references['on_delete'] = "";
        $this->references['on_update'] = "";
    }

    function get_name() {
        return "~".$this->cname;
    }

    function equal_to(Modyllic_Schema_Index $other, array $fromnames=null) {
        if ( ! parent::equal_to($other) )               { return false; }
        if ( $this->references != $other->references ) { return false; }
        if ( $this->weak != $other->weak )             { return false; }
        return true;
    }
}
