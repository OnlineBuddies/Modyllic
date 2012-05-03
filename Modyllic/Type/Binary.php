<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Binary extends Modyllic_Type_Char {
    function charset_collation(Modyllic_Type $other=null) { return ""; }
}
