<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Full MySQL support for Modyllic.  Concepts that MySQL can't store or
 * metadata that's lossy in MySQL is kept in the SQLMETA table
 */
class Modyllic_Generator_MySQL extends Modyllic_Generator_ModyllicSQL {

    function ignore_index(Modyllic_Schema_Index $index ) {
        if ( $index instanceOf Modyllic_Schema_Index_Foreign and $index->weak ) {
            return true;
        }
        else {
            return parent::ignore_index($index);
        }
    }

    function column_aliases( Modyllic_Schema_Column $column ) {}

    function foreign_key_weakly_references(Modyllic_Schema_Index $index) {
        $this->foreign_key_regular_references($index);
    }

    function routine_attr_args($routine) {}
    function routine_attr_proc_returns($routine) {}
    function routine_attr_transactions($routine) {}

    function sql_header() {
        return array(
            "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0",
            $this->_format("SET NAMES %str",array("utf8")),
            );
    }

    function sql_footer() {
        return array( "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS" );
    }

    function table_meta($table) {
        $meta = parent::table_meta($table);
        if ( $table->static != Modyllic_Schema_Table::STATIC_DEFAULT ) {
            $meta["static"] = $table->static;
        }
        return $meta;
    }

    function column_meta( Modyllic_Schema_Column $col) {
        $meta = parent::column_meta( $col );
        if ( count($col->aliases) ) {
            $meta["aliases"] = $col->aliases;
        }
        if ( $col->type instanceOf Modyllic_Type_Boolean ) {
            $meta["type"] = "BOOLEAN";
        }
        else if ( $col->type instanceOf Modyllic_Type_Serial ) {
            $meta["type"] = "SERIAL";
        }
        if ( $col->unique ) {
            $meta["unique"] = $col->unique;
        }
        return $meta;
    }

    function index_meta($index) {
        $meta = parent::index_meta($index);
        if ( $index instanceOf Modyllic_Schema_Index_Foreign ) {
            if ( $index->weak != Modyllic_Schema_Index_Foreign::WEAK_DEFAULT ) {
                $meta["weak"] = $index->weak;
            }
        }
        if ( $index->column_defined ) {
            $meta["column_defined"] = $index->column_defined;
        }
        return $meta;
    }

    function routine_meta($routine) {
        $meta = parent::routine_meta($routine);
        if ( $routine->args_type != Modyllic_Schema_Routine::ARGS_TYPE_DEFAULT ) {
            $meta["args_type"] = $routine->args_type;
        }
        if ( $routine instanceOf Modyllic_Schema_Proc ) {
            if ( $routine->returns["type"] != Modyllic_Schema_Proc::RETURNS_TYPE_DEFAULT ) {
                $meta["returns"] = $routine->returns;
            }
        }
        if ( $routine->txns != Modyllic_Schema_Routine::TXNS_DEFAULT ) {
            $meta["txns"] = $routine->txns;
        }
        return $meta;
    }

    function routine_arg_meta( Modyllic_Schema_Arg $arg ) {
        $meta = parent::routine_arg_meta( $arg );
        if ( $arg->type instanceOf Modyllic_Type_Boolean ) {
            $meta["type"] = "BOOLEAN";
        }
        else if ( $arg->type instanceOf Modyllic_Type_Serial ) {
            $meta["type"] = "SERIAL";
        }
        return $meta;
    }

    function create_sqlmeta() {
        $this->begin_cmd();
        $this->extend( "-- This is used to store metadata used by the schema management tool" );
        $this->extend("CREATE TABLE IF NOT EXISTS SQLMETA (");
        $this->indent();
        $this->begin_list();
        $this->extend("kind CHAR(9) NOT NULL");
        $this->extend("which CHAR(90) NOT NULL");
        $this->extend("value TEXT NOT NULL");
        $this->extend("PRIMARY KEY (kind,which)");
        $this->end_list();
        $this->undent();
        $this->extend(") ENGINE=MyISAM");
        $this->end_cmd();
    }

    function insert_meta($kind,$which,array $meta) {
        if ( ! $meta ) { return; }
        if ( ! isset($this->what['sqlmeta']) ) { return; }
        $this->cmd( "INSERT INTO SQLMETA (kind,which,value) VALUES (%str, %str, %str)",
            $kind, $which, json_encode($meta) );
    }
    function delete_meta($kind,$which) {
        if ( ! isset($this->what['sqlmeta']) ) { return; }
        if ( ! $this->to_sqlmeta_exists ) { return; }
        $this->cmd( "DELETE FROM SQLMETA WHERE kind=%str AND which=%str",
            $kind, $which );
    }
    function update_meta($kind,$which,array $meta) {
        if ( ! isset($this->what['sqlmeta']) ) { return; }
        if ( ! $meta and ! $this->to_sqlmeta_exists ) { return; }
        if ( $meta ) {
            $meta_str = json_encode($meta);
            $this->cmd( "INSERT INTO SQLMETA SET kind=%str, which=%str, value=%str ON DUPLICATE KEY UPDATE value=%str",
                 $kind, $which, $meta_str, $meta_str );
        }
        else {
            $this->delete_meta($kind,$which);
        }
    }

}
