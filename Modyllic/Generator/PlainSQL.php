<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator_PlainSQL extends Modyllic_Generator_StrippedMySQL {
    function __construct( $delim=';;', $sep=true ) {
        error_log("DEPRECATED SQL dialect selected: PlainSQL is now StrippedMySQL");
        parent::__construct($delim,$sep);
    }
}
