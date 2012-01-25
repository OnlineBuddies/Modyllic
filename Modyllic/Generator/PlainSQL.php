<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author astewart@online-buddies.com
 */

class Modyllic_Generator_PlainSQL extends Modyllic_Generator_SQL {
    // We include weak constraints as well as regular ones
    function ignore_index( $index ) {
        return FALSE;
    }
    
    function create_sqlmeta() {}
    function insert_meta($kind,$which,array $what) {}
    function delete_meta($kind,$which) {}
    function update_meta($kind,$which,$what) {}
}
