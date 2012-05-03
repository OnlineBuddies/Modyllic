<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_VarChar extends Modyllic_Type_VarString {
    function binary() {
        $new = new Modyllic_VarBinary("VARBINARY");
        $new->clone_from($this);
        return $new;
    }
}
