<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Blob extends Modyllic_Type_Text {
    function type_name($size) { return $size . "BLOB"; }
    function charset_collation(Modyllic_Type $other=null) { return ""; }
}
