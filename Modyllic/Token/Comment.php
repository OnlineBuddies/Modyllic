<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Comments, both C style and SQL style
 */
class Modyllic_Token_Comment extends Modyllic_Token {
    protected $literal;
    function __construct( $pos, $literal, $value ) {
        parent::__construct( $pos, $value );
        $this->literal = $literal;
    }
    function value() {
        return preg_replace('/\r/','',$this->value);
    }
    function literal() {
        return preg_replace('/\r/','',$this->literal);
    }
}
