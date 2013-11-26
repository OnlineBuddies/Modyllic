<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Expression_Operator_Binary extends Modyllic_Expression {
    public $op;
    public $exp1;
    public $exp2;
    public $type1;
    public $type2;
    function __construct($exp1,$op,$exp2) {
        $this->exp1 = $exp1;
        $this->op = $op;
        $this->exp2 = $exp2;
        switch ($this->op->token()) {
            case 'AND':
            case 'OR':
            case 'XOR':
            case '&&':
            case '||':
                $this->type1 = $this->type2 = Modyllic_Type::create('BOOLEAN'); break;
            case '&':
            case '|':
            case '^':
            case '<<':
            case '>>':
                $this->type1 = $this->type2 = Modyllic_Type::create('BIGINT'); break;
            case 'DIV':
            case 'MOD':
            case '/':
            case '-':
            case '%':
            case '+':
            case '*':
                $this->type = $this->type2 = Modyllic_Type::create('NUMERIC'); break;
            case 'LIKE':
            case 'REGEXP':
            case 'RLIKE':
            case 'SOUNDS':
                $this->type = $this->type2 = Modyllic_Type::create('LONGTEXT'); break;
        }
    }
    function normalize($type) {
        $type1 = $type2 = $type;
        if ($this->type1) $type1 = $this->type1;
        if ($this->type2) $type2 = $this->type2;
        $op = preg_replace('/(\w+)/',' $1 ',$this->op->token());
        return $this->exp1->normalize($type2) . $op . $this->exp2->normalize($type2);
    }
    function to_php() {
        return array($this->op->token(),$this->exp1->to_php(),$this->exp2->to_php());
    }
}
