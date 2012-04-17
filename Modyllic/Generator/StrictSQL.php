<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Generator/SQL.php";

class Modyllic_Generator_StrictSQL extends Modyllic_Generator_SQL {
    // We include weak constraints as well as regular ones
    function ignore_index( $index ) {
        return false;
    }
}
