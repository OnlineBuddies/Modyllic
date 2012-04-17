<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/SQL.php";
require_once "Modyllic/Generator/MySQL.php";
require_once "Modyllic/Schema.php";

class Modyllic_Generator_ModyllicSQL extends Modyllic_Generator_MySQL {
    function sqlmeta_exists($schema) {
        return false;
    }

    // We include weak constraints as well as regular ones
    function ignore_index( $index ) {
        return false;
    }

    function create_sqlmeta() {}
    function insert_meta($kind,$which,array $what) {}
    function delete_meta($kind,$which) {}
    function update_meta($kind,$which,array $meta) {}
    function add_column( $column ) {
        parent::add_column( $column );
        $this->column_aliases($column);
    }
    function create_column( $column ) {
        parent::create_column( $column );
        $this->column_aliases($column);
    }
    function column_aliases( $column ) {
        if ( count($column->aliases) ) {
            $this->add( " ALIASES (" );
            foreach ( $column->aliases as $num=>$alias ) {
                if ($num) { $this->add(", "); }
                $this->add("%id",$alias);
            }
            $this->add( ")" );
        }
    }
    function foreign_key($index) {
        if ( $index->weak != Modyllic_Index_Foreign::WEAK_DEFAULT ) {
            $this->add( " WEAKLY REFERENCES %id", $index->references['table'] );
        }
        else {
            $this->add( " REFERENCES %id", $index->references['table'] );
        }
        $this->add( " (" . implode(",",array_map(array("Modyllic_SQL","quote_ident"),array_map("trim",
                            $index->references['columns'] ))) .")" );
        if ( $index->references['on_delete'] ) {
           $this->add( " ON DELETE %lit", $index->references['on_delete'] );
        }
        if ( $index->references['on_update'] ) {
           $this->add( " ON UPDATE %lit", $index->references['on_update'] );
        }
    }
    function routine_attrs( $routine ) {
        if ( $routine->args_type != Modyllic_Routine::ARGS_TYPE_DEFAULT ) {
            $this->extend("ARGS %lit",$routine->args_type);
        }
        if ( $routine instanceOf Modyllic_Proc ) {
            switch ($routine->returns["type"]) {
            case Modyllic_Proc::RETURNS_TYPE_DEFAULT:
                break;
            case "COLUMN":
            case "LIST":
                $this->extend("RETURNS %lit %lit",$routine->returns["type"], $routine->returns["column"]);
                break;
            case "MAP":
                $this->extend("RETURNS %lit (%lit,%lit)",$routine->returns["type"], $routine->returns["key"], $routine->returns["value"]);
                break;
            default:
                $this->extend("RETURNS %lit",$routine->returns["type"]);
            }
        }
        switch ( $routine->txns ) {
            case Modyllic_Routine::TXNS_DEFAULT:
                break;
            case Modyllic_Routine::TXNS_HAS:
                $this->extend("CONTAINS TRANSACTIONS");
                break;
            case Modyllic_Routine::TXNS_CALL:
                $this->extend("CALL IN TRANSACTION");
                break;
            case Modyllic_Routine::TXNS_NONE:
                $this->extend("NO TRANSACTIONS");
                break;
        }
        parent::routine_attrs( $routine );
    }
}
