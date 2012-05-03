<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * This stores just schema global attributes
 */
class Modyllic_Changeset_Schema {
    public $name;
    public $charset;
    public $collate;
    public $from;

    /**
     * Check to see if anything has actually been changed
     */
    function has_changes() {
        return ( isset($this->charset) or isset($this->collate) );
    }
}

