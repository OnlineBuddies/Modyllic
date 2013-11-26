<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

abstract class Modyllic_Expression {
    abstract function normalize($type);
    function equal_to($exp,$type,$length=null) {
        if (! $this instanceOf $exp) return false;
        if (isset($length) and $length) {
            return (substr($this->normalize($type),0,$length) == substr($exp->normalize($type),0,$length));
        }
        else {
            return ($this->normalize($type) == $exp->normalize($type));
        }
    }
    function create($a1,$a2=null,$a3=null) {
        if (isset($a3)) {
            return self::createBinary($a1,$a2,$a3);
        }
        if (isset($a2)) {
            if (is_array($a2)) {
                return self::createFunction($a1,$a2);
            }
            else {
                return self::createUnary($a1,$a2);
            }
        }
        return self::createValue($a1);
    }
    function createUnary($op,$value) {
        return new Modyllic_Expression_Operator_Unary( new Modyllic_Token_Bareword(0,$op), self::create($value) );
    }
    function createBinary($value1,$op,$value2) {
        return new Modyllic_Expression_Operator_Binary( self::create($value1), new Modyllic_Token_Bareword(0,$op), self::create($value2) );
    }
    function createFunction($func,$args) {
        $inflated_args = array();
        foreach ($args as $arg) {
            $inflated_args = self::create($arg);
        }
        if (! $func instanceOf Modyllic_Token) {
            $func = new Modyllic_Token_Bareword(0,$func);
        }
        return new Modyllic_Expression_Function( $func, $args );
    }
    function createValue($value,$type=null) {
        if ($value instanceOf Modyllic_Expression) {
            return $value;
        }
        if ($value instanceOf Modyllic_Token) {
            return new Modyllic_Expression_Value($value,$type);
        }
        if (is_int($value) or is_float($value)) {
            if (!isset($type)) $type = Modyllic_Type::create('NUMERIC');
            return new Modyllic_Expression_Value(new Modyllic_Token_Num(0,$value),$type);
        }
        if (is_bool($value)) {
            if (!isset($type)) $type = Modyllic_Type::create('BOOLEAN');
            return new Modyllic_Expression_Value(new Modyllic_Token_Num(0,(int)$value),$type);
        }
        return new Modyllic_Expression_Value(new Modyllic_Token_PHPString(0,$value),$type);
    }
}

