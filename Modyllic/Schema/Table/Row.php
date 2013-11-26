<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Schema_Table_Row extends ArrayObject {
    public function inflate($attr,$value) {
        $this[$attr] = Modyllic_Parser::parse_expr($value);
    }
}
