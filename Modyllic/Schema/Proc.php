<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Schema/Routine.php";

class Modyllic_Schema_Proc extends Modyllic_Schema_Routine {
    const RETURNS_TYPE_DEFAULT = "NONE";
    public $returns = array("type"=>self::RETURNS_TYPE_DEFAULT);
    function equal_to(Modyllic_Schema_CodeBody $other) {
        if ( ! parent::equal_to( $other ) ) { return false; }
        if ( $this->returns != $other->returns ) { return false; }
        return true;
    }
}
