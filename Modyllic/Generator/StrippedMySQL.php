<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author astewart@online-buddies.com
 */

/*
 * The stripped MySQL class is ModyllicSQL with the language extensions
 * excised but no metadata table, plus the MySQL stock headers.
 */
class Modyllic_Generator_StrippedMySQL extends Modyllic_Generator_MySQL {

    function table_meta($table) { return array(); }
    function column_meta( Modyllic_Schema_Column $col) { return array(); }
    function index_meta($index) { return array(); }
    function routine_arg_meta( Modyllic_Schema_Arg $arg ) { return array(); }
    function routine_meta($routine) { return array(); }

}
