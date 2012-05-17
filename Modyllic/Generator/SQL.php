<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator_SQL extends Modyllic_Generator_MySQL {
    function __construct( $delim=';;', $sep=true ) {
        error_log("DEPRECATED SQL dialect selected: SQL is now MySQL");
        parent::__construct($delim,$sep);
    }
}
