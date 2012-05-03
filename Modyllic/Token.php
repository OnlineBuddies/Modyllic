<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */
require_once "Modyllic/Token/Whitespace.php";
require_once "Modyllic/Token/Ident.php";

/**
 * These are what are returned by the tokenizer
 */
class Modyllic_Token {
    public $pos;
    protected $value;
    function __construct($pos,$value=null) {
        $this->pos = $pos;
        $this->value = $value;
    }
    /**
     * This is exactly the string that was matched by the tokenizer
     */
    function literal() {
        return $this->value;
    }
    /**
     * The value of the token.  This differs from literal for things like
     * comments, which don't include the comment markers and quoted
     * identifiers which have their quotes removed.
     */
    function value() {
        return $this->value;
    }
    /**
     * The "token" value, for any kind of identifier this is all caps.
     */
    function token() {
        return $this->value;
    }
    /**
     * The token value and class, used in debugging
     */
   function debug() {
       return get_class($this).":'".$this->value."'";
   }
}
