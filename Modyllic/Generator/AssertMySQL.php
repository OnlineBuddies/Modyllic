<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * MySQL, but with so-called weak foreign keys included as regular foreign
 * keys.  This lets you have maximum constraints while developing, but
 * disable them in production for performance reasons.
 */
class Modyllic_Generator_AssertMySQL extends Modyllic_Generator_MySQL {
    function ignore_index( Modyllic_Schema_Index $index ) {
        return false;
    }
}
