<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

abstract class Modyllic_Type_Numeric extends Modyllic_Type {
    public $default_length = 11;
    public $length;
    public $unsigned = false;
    public $zerofill = false;

    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }
    function equal_to(Modyllic_Type $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->unsigned != $other->unsigned ) { return false; }
        if ( $this->zerofill != $other->zerofill ) { return false; }
        if ( $this->length != $other->length) { return false; }
        return true;
    }
    function copy_from(Modyllic_Type $old) {
        parent::copy_from($old);
        $this->unsigned = $old->unsigned;
        $this->zerofill = $old->zerofill;
        $this->length = $old->length;
    }
    function numify($value) {
        if ( $value instanceOf Modyllic_Token_String ) {
            $plain = $value->unquote() + 0;
        }
        else if ( $value instanceOf Modyllic_Token_Num ) {
            $plain = $value->value() + 0;
        }
        else {
            $plain = $value + 0;
        }
        return $plain;
    }
}
