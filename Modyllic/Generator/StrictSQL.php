<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator_StrictSQL extends Modyllic_Generator_AssertMySQL {
    function __construct( $delim=';;', $sep=true ) {
        error_log("DEPRECATED SQL dialect selected: StrictSQL is now AssertMySQL");
        parent::__construct($delim,$sep);
    }
}
