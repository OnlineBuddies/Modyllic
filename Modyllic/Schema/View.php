<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Schema_View extends Modyllic_Diffable {
    public $name;
    public $def;
    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }
    function equal_to( $other ) {
        if ( $this->def != $other->def ) { return false; }
        return true;
    }
}
