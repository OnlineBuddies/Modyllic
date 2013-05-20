<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Bareword type tokens
 */
class Modyllic_Token_Bareword extends Modyllic_Token implements Modyllic_Token_Ident {
    private $upper;
    function token() {
        if ( isset($this->upper) ) {
            return $this->upper;
        }
        else {
            return $this->upper = strtoupper($this->value());
        }
    }
    function is_reserved() {
        return Modyllic_SQL::is_reserved($this->token());
    }
    function is_ident() {
        return ! $this->is_reserved();
    }
}
