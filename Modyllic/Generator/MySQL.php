<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Full MySQL support for Modyllic.  Concepts that MySQL can't store or
 * metadata that's lossy in MySQL is kept in the metadata table
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

    function emit_type( $type, $from_type = null ) {
        if ( $type instanceOf Modyllic_Type_Serial ) {
            $bigint = Modyllic_Type::create("BIGINT");
            $bigint->unsigned = true;
            $this->add( $bigint->to_sql() );
        }
        else {
            parent::emit_type( $type, $from_type );
        }
    }

    function column_auto_increment( Modyllic_Schema_Column $column ) {
        return $column->auto_increment;
    }

    function column_not_null( Modyllic_Schema_Column $column ) {
        return ! $column->null;
    }

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

    function create_meta() {
        $table = new Modyllic_Schema_Table( "MODYLLIC" );
        $table->docs = "This is used to store metadata used by the schema management tool";

        $kind = $table->add_column( new Modyllic_Schema_Column("kind") );
        $kind->type = Modyllic_Type::create("CHAR");
        $kind->type->length = 9;
        $kind->null = false;
        $kind->default = null;

        $which = $table->add_column( new Modyllic_Schema_Column("which") );
        $which->type = Modyllic_Type::create("CHAR");
        $which->type->length = 90;
        $which->null = false;
        $which->default = null;

        $value = $table->add_column( new Modyllic_Schema_Column("value") );
        $value->type = Modyllic_Type::create("VARCHAR");
        $value->type->length = 60000;
        $value->null = false;
        $value->default = null;

        $pk = $table->add_index( new Modyllic_Schema_Index("!PRIMARY KEY") );
        $pk->primary = true;
        $pk->columns = array( "kind" => false, "which" => false );

        if ( $this->source instanceOf Modyllic_Changeset ) {
            $schema = $this->source->schema;
        }
        else {
            $schema = $this->source;
        }

        $this->create_table( $table, $schema );
    }

    function insert_meta($kind,$which,array $meta) {
        if ( ! $meta ) { return; }
        if ( ! isset($this->what['meta']) ) { return; }
        $this->cmd( "INSERT INTO MODYLLIC (kind,which,value) VALUES (%str, %str, %str)",
            $kind, $which, json_encode($meta) );
    }
    function delete_meta($kind,$which) {
        if ( ! isset($this->what['meta']) ) { return; }
        if ( ! $this->to_meta_exists ) { return; }
        $this->cmd( "DELETE FROM MODYLLIC WHERE kind=%str AND which=%str",
            $kind, $which );
    }
    function update_meta($kind,$which,array $meta) {
        if ( ! isset($this->what['meta']) ) { return; }
        if ( ! $meta and ! $this->to_meta_exists ) { return; }
        if ( $meta ) {
            $meta_str = json_encode($meta);
            $this->cmd( "INSERT INTO MODYLLIC SET kind=%str, which=%str, value=%str ON DUPLICATE KEY UPDATE value=%str",
                 $kind, $which, $meta_str, $meta_str );
        }
        else {
            $this->delete_meta($kind,$which);
        }
    }

}
