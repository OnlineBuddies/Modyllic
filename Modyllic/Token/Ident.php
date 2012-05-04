<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Identifiers, eg, column names, table names, etc.
 */
class Modyllic_Token_Ident extends Modyllic_Token {
    private $upper;
    function token() {
        if ( isset($this->upper) ) {
            return $this->upper;
        }
        else {
            return $this->upper = strtoupper($this->value());
        }
    }
}
