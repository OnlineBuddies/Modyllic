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

    function schema_types() {
        return array('database','meta','tables','views','routines','events','triggers');
    }

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
            $this->_format("SET NAMES %str",array("utf8mb4")),
            "SET foreign_key_checks = 0"
            );
    }

    function sql_footer() {
        return array(
            "SET foreign_key_checks = 1"
        );
    }

    function table_meta($table) {
        $meta = parent::table_meta($table);
        if ( $table->static != Modyllic_Schema_Table::STATIC_DEFAULT ) {
            $meta["static"] = $table->static;
        }
        return $meta;
    }

    function data_meta($table,$row) {
        $meta = parent::data_meta($table,$row);
        foreach ($row as $col=>$val) {
            if ($val instanceOf Modyllic_Expression_Value) continue;
            $meta[$col] = $val->normalize($table->columns[$col]->type);
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

    function event_meta($event) {
        $meta = parent::event_meta($event);
        $meta["schedule"] = $event->schedule->schedule;
        $meta["starts"] = $event->schedule->starts;
        $meta["ends"] = $event->schedule->ends;
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

    function queue_remove_foreign_keys($indexes) {}
    function queue_add_foreign_keys($indexes) {}

    private function is_table_schema_changed($tables,$name) {
        if (! isset($tables[$name])) return false;
        return $tables[$name]->has_schema_changes();
    }

    function alter_tables($tables) {
        // Here we disable all of the foreign keys in the tables we're modifying and that reference the tables that we're modifying
        foreach ( $this->source->from->tables as $table ) {
            $todrop = array();
            foreach ( $table->indexes as $index ) {
                if (! $index instanceOf Modyllic_Schema_Index_Foreign) continue;
                if (! $this->is_table_schema_changed($tables,$table->name) and ! $this->is_table_schema_changed($tables,$index->references['table'])) continue;
                if (isset($this->source->changeset->remove['tables'][$table->name])) continue;
                $todrop[] = $index;
            }
            if (count($todrop)) {
                $this->begin_alter_table($table);
                foreach ($todrop as $index) {
                    $this->drop_index($index);
                }
                $this->end_alter_table($table);
            }
        }

        // then alter tables as we usually do
        foreach ( $tables as $table ) {
            $this->alter_table($table);
        }

        // then recreate the constraints we removed
        foreach ( $this->source->to->tables as $table ) {
            $toadd = array();
            foreach ( $table->indexes as $index ) {
                if (! $index instanceOf Modyllic_Schema_Index_Foreign) continue;
                if (! $this->is_table_schema_changed($tables,$table->name) and ! $this->is_table_schema_changed($tables,$index->references['table'])) continue;
                if (isset($this->source->changeset->remove['tables'][$table->name])) continue;
                $toadd[] = $index;
            }
            if (count($toadd)) {
                $this->begin_alter_table($table);
                foreach ($toadd as $index) {
                    $this->add_index($index);
                }
                $this->end_alter_table($table);
            }
        }
    }

    function drop_database($schema) {
        if ($schema->name_is_default) return $this;
        $this->cmd( "DROP DATABASE IF EXISTS %id", $schema->name );
        return $this;
    }

    function drop_table( Modyllic_Schema_Table $table ) {
        if ( ! isset($this->what['meta']) and $table instanceOf Modyllic_Schema_MetaTable ) { return; }
        $this->cmd( "DROP TABLE IF EXISTS %id", $table->name );
        return $this;
    }

    function drop_view( $view ) {
        $this->cmd( "DROP VIEW IF EXISTS %id", $view->name );
        return $this;
    }

    function drop_function( $func ) {
        $this->cmd( "DROP FUNCTION IF EXISTS %id", $func->name );
        return $this;
    }

    function drop_procedure( $proc ) {
        $this->cmd( "DROP PROCEDURE IF EXISTS %id", $proc->name );
        return $this;
    }

    function drop_trigger( $trigger ) {
        $this->cmd( "DROP TRIGGER IF EXISTS %id", $trigger->name );
    }

    function drop_event( $event ) {
        $this->cmd( "DROP EVENT IF EXISTS %id", $event->name );
        return $this;
    }

    function truncate_table($table) {
        $target = isset($table->to) ? $table->to : $table;
        $this->cmd("DELETE FROM %id", $target->name);
        foreach ($target->columns as $col) {
            if (! $col->auto_increment) continue;
            $this->cmd("ALTER TABLE %id AUTO_INCREMENT = 1", $target->name);
            break;
        }
    }

}
