<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Reserved words
 */
class Modyllic_Token_Reserved extends Modyllic_Token implements Modyllic_Token_Bareword {
    private $upper;
    function token() {
        if ( isset($this->upper) ) {
            return $this->upper;
        }
        else {
            return $this->upper = strtoupper($this->value());
        }
    }
}
