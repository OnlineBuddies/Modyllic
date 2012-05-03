<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_VarBinary extends Modyllic_VarString {
    function charset_collation(Modyllic_Type $other=null) { return ""; }
}
