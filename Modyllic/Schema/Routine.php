<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing a stored routine
 */
abstract class Modyllic_Schema_Routine extends Modyllic_Schema_CodeBody {
    public $name;
    public $args = array();
    const ARGS_TYPE_DEFAULT = "LIST";
    public $args_type = self::ARGS_TYPE_DEFAULT;
    const DETERMINISTIC_DEFAULT = false;
    public $deterministic = self::DETERMINISTIC_DEFAULT;
    const ACCESS_DEFAULT = "CONTAINS SQL";
    public $access = self::ACCESS_DEFAULT;
    public $returns;
    const TXNS_NONE = 0;
    const TXNS_CALL = 1;
    const TXNS_HAS  = 2;
    const TXNS_DEFAULT = self::TXNS_NONE;
    public $txns = self::TXNS_DEFAULT;
    public $docs = '';

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }

    /**
     * @param Modyllic_Schema_CodeBody $other
     * @returns bool True if $other is equivalent to $this
     */
    function equal_to(Modyllic_Schema_CodeBody $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->deterministic != $other->deterministic ) { return false; }
        if ( $this->access        != $other->access )        { return false; }
        if ( $this->args_type     != $other->args_type )     { return false; }
        if ( $this->txns          != $other->txns )          { return false; }
        $thisargc = count($this->args);
        $otherargc = count($other->args);
        if ( $thisargc != $otherargc ) { return false; }
        for ( $ii=0; $ii<$thisargc; ++$ii ) {
            if ( ! $this->args[$ii]->equal_to( $other->args[$ii] ) ) { return false; }
        }
        return true;
    }
    function validate() {
        return array();
    }
}
