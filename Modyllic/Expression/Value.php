<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Expression_Value extends Modyllic_Expression {
    public $token;
    public $type;
    function __construct($value,$type=null) {
        if (! $value instanceOf Modyllic_Token) {
            throw new Exception("Values must be created from tokens");
        }
        $this->token = $value;
        $this->type = $type;
    }
    function setType($type) {
        $this->type = $type;
    }
    function normalize($type) {
        if (isset($this->type)) $type = $this->type;
        if ($this->token instanceOf Modyllic_Token_Ident and $this->token->is_ident()) {
            return Modyllic_SQL::quote_ident($this->token->unquote());
        }
        else if ($this->token instanceOf Modyllic_Token_Bareword or $this->token instanceOf Modyllic_Token_Symbol) {
            return $this->token->token();
        }
        else {
            return $type->normalize($this->token);
        }
    }
    function to_php() {
        return $this->token->unquote();
    }
}
