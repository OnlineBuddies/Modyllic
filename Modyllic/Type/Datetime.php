<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Datetime extends Modyllic_Type {
    function normalize($date) {
        $is_object = is_object($date);
        $value = $is_object ? $date->value() : $date;
        $unquoted = $is_object ? $date->unquote() : $date;
        if ( $date instanceOf Modyllic_Token_Reserved or (!$is_object and Modyllic_SQL::is_reserved($value) ) ) {
            return $value;
        }
        if ( $value == 0 ) {
            return "'0000-00-00 00:00:00'";
        }
        if ( $is_object and ! $date instanceOf Modyllic_Token_String ) {
            throw new Exception("Invalid date value: ".$date->debug());
        }
        if ( preg_match( '/^(\d{1,4})-(\d\d?)-(\d\d?)(?: (\d\d?)(?::(\d\d?)(?::(\d\d?))?)?)?$/', $unquoted, $matches ) ) {
            $year = $matches[1];
            $mon  = $matches[2];
            $day  = $matches[3];
            $hour = isset($matches[4])? $matches[4] : 0;
            $min  = isset($matches[5])? $matches[5] : 0;
            $sec  = isset($matches[6])? $matches[6] : 0;
            #list( $full, $year, $mon, $day, $hour, $min, $sec ) = $matches;
            return sprintf("'%04d-%02d-%02d %02d:%02d:%02d'", $year, $mon, $day, $hour, $min, $sec );
        }
        else {
            throw new Exception("Invalid date value: ".$date->debug());
        }
    }
}
