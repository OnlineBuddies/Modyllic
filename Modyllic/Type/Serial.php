<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Serial extends Modyllic_Type_BigInt {
    public $unsigned = true;
    function to_sql() {
        return $this->name;
    }
}

