<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Whitespace chunks, including newlines
 */
class Modyllic_Token_Whitespace extends Modyllic_Token {
    function value() {
        return preg_replace('/\r/','',$this->value);
    }
}
