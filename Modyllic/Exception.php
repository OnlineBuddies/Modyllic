<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Our exception class, it takes a bunch of useful debugging information
 */
class Modyllic_Exception extends Exception {
    /**
     * @param string $filename
     * @param int $line
     * @param int $col
     * @param string $context
     * @param string $message
     */
    function __construct( $filename, $line, $col, $context, $message ) {
        parent::__construct("$message while parsing SQL in $filename on line $line at col $col:\n\n$context" );
    }
}
