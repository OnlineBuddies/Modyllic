<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Expression_Function extends Modyllic_Expression {
    public $func;
    public $args;
    function __construct($func,$args) {
        $this->func = $func;
        $this->args = $args;
    }
    function normalize($type) {
        $str_args = array();
        foreach ($this->args as $arg) {
            $str_args[] = $arg->normalize(Modyllic_Type::create('LONGTEXT'));
        }
        return $this->func->value() . '(' . implode(', ',$str_args) . ')';
    }
    function to_php() {
        $php_args = array();
        foreach ($this->args as $arg) {
            $php_args[] = $arg->to_php();
        }
        return array( $this->func->value(), $php_args );
    }
}

