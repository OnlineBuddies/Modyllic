<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Generator/MySQL.php";

class Modyllic_Generator_AssertMySQL extends Modyllic_Generator_MySQL {
    // We include weak constraints as well as regular ones
    function ignore_index( Modyllic_Schema_Index $index ) {
        return false;
    }
}
