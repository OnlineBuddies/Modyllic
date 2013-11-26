<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Expression_Operator_Unary extends Modyllic_Expression {
    public $op;
    public $expression;
    public $type;
    function __construct($op,$exp) {
        $this->op = $op;
        $this->expression = $exp;
        switch ($this->op) {
            case 'BINARY': $this->type = Modyllic_Type::create('BINARY'); break;
            case '~': $this->type = Modyllic_Type::create('BIGINT'); break;
            case '!': $this->type = Modyllic_Type::create('BOOLEAN'); break;
            case '-': $this->type = Modyllic_Type::create('NUMERIC'); break;
        }
    }
    function normalize($type) {
        return $this->op->token() . $this->expression->normalize($this->type);
    }
    function to_php() {
        return array($this->op->token(), $this->expression->to_php());
    }
}
