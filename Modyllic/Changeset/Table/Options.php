<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Changeset_Table_Options {
    public $charset;
    public $collate;
    public $engine;

    /**
     * @returns true if this object contains any changes
     */
    function has_changes() {
        return isset($this->charset) or isset($this->collate) or isset($this->engine);
    }
}

