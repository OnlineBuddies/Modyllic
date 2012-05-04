<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Indicates that we've encountered a tokenization error.  Tokenization
 * errors do not consume any of the input string and as such, once you hit
 * one it will always be returned by next().
 */
class Modyllic_Token_Error extends Modyllic_Token_Except {
    protected $row;
    protected $col;
    function __construct($pos, $row,$col) {
        $this->pos = $pos;
        $this->row = $row;
        $this->col = $col;
    }
    function value() {
        return "Syntax error";
    }
}
