<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

abstract class Modyllic_Type_String extends Modyllic_Type {
    protected $default_charset = "utf8";
    protected $default_collate = "utf8_general_ci";
    private $charset;
    private $collate;

    function set_default_charset($value) {
        $this->default_charset = $value;
    }
    function set_default_collate($value) {
        $this->default_collate = $value;
    }

    function charset($value=null) {
        $args = func_num_args();
        if ( $args ) {
            $this->charset = $value;
        }
        else {
            return isset($this->charset) ? $this->charset : $this->default_charset;
        }
    }

    function collate($value=null) {
        $args = func_num_args();
        if ( $args ) {
            $this->collate = $value;
        }
        else {
            return isset($this->collate) ? $this->collate : $this->default_collate;
        }
    }

    function equal_to(Modyllic_Type $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->charset() != $other->charset() ) { return false; }
        if ( $this->collate() != $other->collate() ) { return false; }
        return true;
    }
    function copy_from(Modyllic_Type $old) {
        parent::copy_from($old);
        $this->charset( $old->charset() );
        $this->collate( $old->collate() );
    }


    function normalize($str) {
        if ( $str instanceOf Modyllic_Token_Bareword and Modyllic_SQL::is_reserved($str->token()) ) {
            return $str->value();
        }
        elseif ( $str instanceOf Modyllic_Token_Bareword ) {
            return Modyllic_SQL::quote_ident($str->unquote());
        }
        else if ( $str instanceOf Modyllic_Token_String ) {
            $value = $str->unquote();
        }
        else if ( $str instanceOf Modyllic_Token_Num ) {
            $value = $str->value();
        }
        else if ( ! is_object($str) ) {
            $value = $str;
        }
        else {
            throw new Exception( "Expected a valid string, got: $str" );
        }
        if ( !is_null($this->length()) and $this->length()<=Modyllic_Expression::MAX_SUBSTR_LENGTH ) {
            $value = mb_substr( $value, 0, $this->length(), 'UTF-8' );
        }
        return Modyllic_SQL::quote_str( $value );
    }
    function charset_collation(Modyllic_Type $other=null) {
        $other_charset = $other instanceOf Modyllic_Type_String? $other->charset(): $this->default_charset;
        $other_collate = $other instanceOf Modyllic_Type_String? $other->collate(): $this->default_collate;
        $diff_charset = $this->charset() != $other_charset;
        $diff_collate = $this->collate() != $other_collate;
        if ( $diff_charset or $diff_collate ) {
            if ( $this->charset() == "latin1" and $this->collate() == "latin1_general_ci" ) {
                return " ASCII";
            }
            if ( $this->charset() == "ucs2" and $this->collate() == "ucs2_general_ci" ) {
                return " UNICODE";
            }
        }
        $sql = "";
        if ( $diff_charset ) {
            $sql .= " CHARACTER SET ".$this->charset();
        }
        if ( $diff_collate ) {
            if ( preg_match('/_bin$/u', $this->collate() ) ) {
                $sql .= " BINARY";
            }
            else {
                $sql .= " COLLATE ".$this->collate();
            }
        }
        return $sql;
    }
}
