<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_VarChar extends Modyllic_Type_VarString {
    function make_binary() {
        $new = new Modyllic_Type_VarBinary("VARBINARY");
        $new->clone_from($this);
        return $new;
    }
}
