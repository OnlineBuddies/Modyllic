<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Date extends Modyllic_Type {
    function normalize($date) {
        if ( $date instanceOf Modyllic_Token_Reserved ) {
            return $date->value();
        }
        if ( $date instanceOf Modyllic_Token_Num ) {
            if ( $date->value() == 0 ) {
                return "'0000-00-00'";
            }
            else {
                throw new Exception("Invalid default for date: $date");
            }
        }
        if ( ! $date instanceOf Modyllic_Token_String ) {
            throw new Exception("Invalid default for date: $date");
        }
        if ( $date->value() == '0' ) {
            return "'0000-00-00'";
        }
        if ( ! preg_match( '/^\d\d\d\d-\d\d-\d\d$/', $date->unquote() ) ) {
            throw new Exception("Invalid default for date: $date");
        }
        return $date->value();
    }
}
