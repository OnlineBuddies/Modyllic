<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * The default metadata table
 */
class Modyllic_Schema_MetaTable extends Modyllic_Schema_Table {
    function __construct($name = 'MODYLLIC') {
        parent::__construct($name);
        $this->docs = 'This is used to store metadata used by the schema management tool';
        $this->static = true;

        $kind = $this->add_column( new Modyllic_Schema_Column("kind") );
        $kind->type = Modyllic_Type::create("CHAR");
        $kind->type->length = 9;
        $kind->null = false;
        $kind->default = null;

        $which = $this->add_column( new Modyllic_Schema_Column("which") );
        $which->type = Modyllic_Type::create("CHAR");
        $which->type->length = 90;
        $which->null = false;
        $which->default = null;

        $value = $this->add_column( new Modyllic_Schema_Column("value") );
        $value->type = Modyllic_Type::create("VARCHAR");
        $value->type->length = 60000;
        $value->null = false;
        $value->default = null;

        $pk = $this->add_index( new Modyllic_Schema_Index("!PRIMARY KEY") );
        $pk->primary = true;
        $pk->columns = array( "kind" => false, "which" => false );
    }

    function add_metadata($kind, $name, $value) {
        if (count($value) == 0 ) {
            return;
        }
        $this->add_row(array( "kind"=>$kind, "which"=>$name, "value"=>json_encode($value) ));
    }
}
