<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * 
 */
class Modyllic_Token_Reserved extends Modyllic_Token_Bareword {
    function is_reserved() {
        return true;
    }
}

