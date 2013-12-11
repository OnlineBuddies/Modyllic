<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * This is a combination of SeparateAlterMySQL and AssertMySQL, until such time as we
 * hack mixins sideways into 5.2 =/
 */
class Modyllic_Generator_SeparateAlterAssertMySQL extends Modyllic_Generator_SeparateAlterMySQL {
    function ignore_index( Modyllic_Schema_Index $index ) {
        return false;
    }
}
