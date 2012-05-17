<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator_NativeSQL extends Modyllic_Generator_ModyllicSQL {
    function __construct( $delim=';;', $sep=true ) {
        error_log("DEPRECATED SQL dialect selected: NativeSQL is now ModyllicSQL");
        parent::__construct($delim,$sep);
    }
}
