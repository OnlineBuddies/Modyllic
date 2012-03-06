<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/SQL.php";
require_once "Modyllic/Schema.php";

class Modyllic_Generator_SQL {

// ALTER

    function alter_sql( Modyllic_Diff $diff, $delim=";;", $sep=TRUE ) {
        $this->alter( $diff );
        return $this->sql_document( $delim, $sep );
    }

    function alter( Modyllic_Diff $diff ) {
        if ( ! $diff->changeset->has_changes() ) {
            $this->cmd("-- No changes detected.");
            return $this;
        }
        $this->alter_database( $diff->changeset->schema );

        if ( $diff->changeset->create_sqlmeta ) {
            $this->create_sqlmeta();
        }

        $this->drop_events( $diff->changeset->remove['events'] );
        $this->drop_routines( $diff->changeset->remove['routines'] );
        $this->drop_views( $diff->changeset->remove['views'] );
        $this->drop_tables( $diff->changeset->remove['tables'] );

        $this->create_tables( $diff->changeset->add['tables'], $diff->changeset->schema );
        $this->create_views( $diff->changeset->add['views'] );
        $this->create_routines( $diff->changeset->add['routines'] );
        $this->create_events( $diff->changeset->add['events'] );

        $this->alter_tables( $diff->changeset->update['tables'] );
        $this->alter_views( $diff->changeset->update['views'] );
        $this->alter_routines( $diff->changeset->update['routines'] );
        $this->alter_events( $diff->changeset->update['events'] );
        return $this;
    }

// CREATE

    function create_sql( Modyllic_Schema $schema, $delim=";;", $sep=TRUE  ) {
        $this->create( $schema );
        return $this->sql_document( $delim, $sep );
    }

    function create( Modyllic_Schema $schema, $delim=";;", $sep=TRUE  ) {
        $this->create_database( $schema );
        $this->create_sqlmeta();
        $this->create_tables( $schema->tables, $schema );
        $this->create_views( $schema->views );
        $this->create_routines( $schema->routines );
        $this->create_events( $schema->events );
        return $this;
    }

    function create_sqlmeta() {
        $this->begin_cmd();
        $this->extend( "-- This is used to store metadata used by the schema management tool" );
        $this->extend("CREATE TABLE SQLMETA (");
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

// DROP

    function drop_sql( Modyllic_Schema $schema, $delim=";", $sep=FALSE  ) {
        $this->drop($schema);
        return $this->sql_document( $delim, $sep );
    }

    function drop( Modyllic_Schema $schema ) {
        $this->drop_events( $schema->events );
        $this->drop_routines( $schema->routines );
        $this->drop_views( $schema->views );
        $this->drop_tables( $schema->tables );
        $this->drop_database( $schema );
        return $this;
    }

// DATABASE

    function create_database($schema) {
        if ($schema->nameIsDefault) return $this;
        $this->begin_cmd( "CREATE DATABASE %id", $schema->name );
        $this->extend( "DEFAULT CHARACTER SET=%lit", $schema->charset );
        $this->extend( "DEFAULT COLLATE=%lit", $schema->collate );
        $this->end_cmd();
        $this->cmd( "USE %id", $schema->name );
        return $this;
    }

    function alter_database($schema) {
        if ( ! $schema->has_changes() ) {
            return $this;
        }
        $this->begin_cmd( "ALTER DATABASE %id", $schema->from->name);
        if ( isset( $schema->charset ) ) {
            $this->reindent("--  ");
            $this->extend("PREVIOUS VALUE        %lit", $schema->from->charset);
            $this->reindent();
            $this->extend("DEFAULT CHARACTER SET=%lit", $schema->charset);
        }
        if ( isset( $schema->collate ) ) {
            $this->reindent("--  ");
            $this->extend("PREVIOUS VALUE  %lit", $schema->from->collate );
            $this->reindent();
            $this->extend("DEFAULT COLLATE=%lit", $schema->collate );
        }
        $this->end_cmd();
        return $this;
    }

    function drop_database($schema) {
        if ($schema->nameIsDefault) return $this;
        $this->cmd( "DROP DATABASE %id", $schema->name );
        return $this;
    }

// TABLES

    function create_tables( $tables, $schema ) {
        foreach ( $tables as $table ) {
            $this->create_table($table, $schema);
        }
        return $this;
    }

    function alter_tables( $tables ) {
        foreach ( $tables as $table ) {
            $this->alter_table($table);
        }
        return $this;
    }

    function drop_tables( $tables ) {
        foreach ( array_reverse($tables) as $table ) {
            $this->drop_table($table);
        }
        return $this;
    }

// VIEWS

    function create_views( $views ) {
        foreach ( $views as $view ) {
            $this->create_view($view);
        }
        return $this;
    }

    function alter_views( $views ) {
        foreach ( $views as $view ) {
            $this->alter_view($view);
        }
        return $this;
    }

    function drop_views( $views ) {
        foreach ( array_reverse($views) as $view ) {
            $this->drop_view($view);
        }
        return $this;
    }

// ROUTINES

    function create_routines( $routines ) {
        foreach ( $routines as $routine ) {
            $this->create_routine($routine);
        }
        return $this;
    }

    function alter_routines( $routines ) {
        foreach ( $routines as $routine ) {
            $this->alter_routine($routine);
        }
        return $this;
    }

    function drop_routines( $routines ) {
        foreach ( array_reverse($routines) as $routine ) {
            $this->drop_routine($routine);
        }
        return $this;
    }

// EVENTS

    function create_events( $events ) {
        foreach ( $events as $event ) {
            $this->create_event($event);
        }
        return $this;
    }

    function alter_events( $events ) {
        foreach ( $events as $event ) {
            $this->alter_event($event);
        }
        return $this;
    }

    function drop_events( $events ) {
        foreach ( array_reverse($events) as $event ) {
            $this->drop_event($event);
        }
        return $this;
    }

// TABLE

    function table_meta($table) {
        if ( $table->static != Modyllic_Table::STATIC_DEFAULT ) {
            return array( "static"=>$table->static );
        }
        else {
            return array();
        }
    }

    function create_table( $table, $schema ) {
        $this->begin_cmd();
        $this->table_docs( $table );
        $this->extend( "CREATE TABLE %id (", $table->name );
        $this->indent();
        $indexes = $table->indexes;
        $entries = count($table->columns) + count($indexes);
        $completed = 0;
        foreach ( $table->columns as $column ) {
            $this->create_column( $column );
            if ( $column->primary ) {
                unset($indexes['!PRIMARY KEY']);
                $entries --;
            }
            if ( ++$completed < $entries ) {
                $this->add(",");
            }
            if ( $column->docs ) {
                $this->add( " -- ".$column->docs );
            }
        }
        ksort($indexes);
        foreach ( $indexes as $index ) {
            $this->create_index( $index );
            if ( ++$completed < $entries ) {
                $this->add(",");
            }
        }
        $this->undent();
        $this->partial(") ");
        $this->indent();
        $this->reindent("  ", true);
        $this->table_options( $table, $schema );
        $this->end_cmd();

        $this->create_table_data( $table );
        $this->insert_meta( "TABLE", $table->name, $this->table_meta($table) );
        foreach ( $table->columns as $column ) {
            $this->insert_meta( "COLUMN", $table->name . "." . $column->name, $this->column_meta($column) );
        }
        foreach ( $table->indexes as $index ) {
            $this->insert_meta( "INDEX", $table->name . "." . $index->name, $this->index_meta($index) );
        }
        return $this;
    }

    function alter_table( $table ) {
        if ( $table->has_schema_changes() ) {
            if ( $table->options->has_changes() or
                 count($table->add['columns'])+count($table->remove['columns'])+count($table->update['columns'])+
                 count($table->add['indexes'])+count($table->remove['indexes']) > 0 ) {
                $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                $this->begin_list();
                if ($table->options->has_changes()) {
                    $this->table_options( $table->options );
                }
                foreach ($table->add['columns'] as $column) {
                    $this->add_column( $column );
                }
                foreach ($table->remove['columns'] as $column) {
                    $this->drop_column($column);
                }
                foreach ($table->update['columns'] as $column) {
                    $this->alter_column($column);
                }
                foreach ($table->remove['indexes'] as $index) {
                    $this->drop_index($index);
                }
                foreach ($table->add['indexes'] as $index) {
                    $this->add_index($index);
                }
                $this->end_list();
                $this->end_cmd();
            }

            $tometa = $this->table_meta($table);
            $frommeta = $this->table_meta($table->from);
            if ( $tometa != $frommeta ) {
                if ( count($tometa) == 0 ) {
                    $this->delete_meta( "TABLE", $table->name );
                }
                else if ( count($frommeta) == 0 ) {
                    $this->insert_meta( "TABLE", $table->name, $tometa );
                }
                else {
                    $this->update_meta( "TABLE", $table->name, $tometa );
                }
            }
            foreach ($table->add['columns'] as $column) {
                $this->insert_meta( "COLUMN", $table->name . "." . $column->name, $this->column_meta($column) );
            }
            foreach ($table->remove['columns'] as $column) {
                $this->delete_meta( "COLUMN", $table->name . "." . $column->name );
            }
            foreach ($table->update['columns'] as $column) {
                $this->update_meta( "COLUMN", $table->name . "." . $column->name, $this->column_meta($column) );
            }
            foreach ($table->remove['indexes'] as $index) {
                $this->delete_meta( "INDEX", $table->name . "." . $index->name );
            }
            foreach ($table->add['indexes'] as $index) {
                $this->insert_meta( "INDEX", $table->name . "." . $index->name, $this->index_meta($index) );
            }
        }

        if ( isset($table->static) and $table->static ) {
            $this->cmd("TRUNCATE %id", $table->name);
        }
        foreach ($table->remove['data'] as $row ) {
            $this->begin_cmd();
            $this->partial( "DELETE FROM %id WHERE ", $table->name);
            $this->begin_list( " AND " );
            foreach ($row as $col=>$val) {
                $this->next_list_item();
                $this->partial( "%id=%lit", $col, $val );
            }
            $this->end_list();
            $this->end_cmd();
        }
        foreach ($table->update['data'] as $row ) {
            $this->begin_cmd();
            $this->partial( "UPDATE %id SET ", $table->name );
            $this->begin_list();
            foreach ($row['updated'] as $col=>$val) {
                $this->next_list_item();
                $this->partial( "%id=%lit", $col, $val );
            }
            $this->end_list();
            $this->partial(" WHERE ");
            $this->begin_list(" AND ");
            foreach ($row['where'] as $col=>$val) {
                $this->next_list_item();
                $this->partial( "%id=%lit", $col, $val );
            }
            $this->end_list();
            $this->end_cmd();
        }
        foreach ($table->add['data'] as $row ) {
            $this->create_data( $table, $row );
        }
        return $this;
    }

    function drop_table( $table ) {
        $this->cmd( "DROP TABLE %id", $table->name );
        if ( count($this->table_meta($table)) > 0 ) {
            $this->delete_meta("TABLE",$table->name);
        }
        foreach ( $table->columns as $column ) {
            $this->delete_meta("COLUMN", $table->name . "." . $column->name );
        }
        foreach ( $table->indexes as $index ) {
            $this->delete_meta("INDEX", $table->name . "." . $index->name );
        }
        return $this;
    }

    function column_meta($col) {
        if ( count($col->aliases) ) {
            return array( "aliases" => $col->aliases );
        }
        else {
            return array();
        }
    }

    function add_column( $column ) {
        $this->partial("ADD COLUMN " );
        $this->create_column($column);
        if ( $column->after == "" ) {
            $this->add( " FIRST" );
        }
        else {
            $this->add( " AFTER %id", $column->after );
        }
        return $this;
    }

    function alter_column( $column ) {
        if ( $column->from ) {
            $this->reindent("--  ");
            $this->partial( "BEFORE        ");
            $this->create_column( $column->from );
            $this->reindent();
        }
        if ( $column->previously != $column->name ) {
            $this->partial( "CHANGE COLUMN %id ", $column->previously );
        }
        else {
            $this->partial( "MODIFY COLUMN " );
        }
        $this->create_column($column);
        return $this;
    }

    function drop_column( $column ) {
        $this->extend("DROP COLUMN %id", $column->name);
        return $this;
    }

    function create_column( $column ) {
        if ( isset($column->from) ) {
            $this->extend("%id %lit", $column->name, $column->type->toSql($column->from->type) );
        }
        else {
            $this->extend("%id %lit", $column->name, $column->type->toSql() );
        }
        if ( ! $column->null ) {
            $this->add( " NOT NULL" );
        }
        if ( $column->auto_increment ) {
            $this->add( " auto_increment" );
        }
        if ( ! is_null($column->default) ) {
            if ( !$column->null or $column->default!='NULL' ) {
                $this->add( " DEFAULT %lit", $column->default );
            }
        }
        if ( $column->on_update ) {
            $this->add( " ON UPDATE %lit", $column->on_update );
        }
        if ( $column->is_primary ) {
            $this->add( " PRIMARY KEY" );
        }
        return $this;
    }

    function index_meta($index) {
        if ( $index instanceOf Modyllic_Index_Foreign ) {
            if ( $index->weak != Modyllic_Index_Foreign::WEAK_DEFAULT ) {
                return array( "weak" => $index->weak );
            }
            else {
                return array();
            }
        }
        else {
            return array();
        }
    }

    function add_index( $index ) {
        $this->create_index($index, "ADD ");
        return $this;
    }

    function drop_index( $index ) {
        if ( $index instanceOf Modyllic_Index_Foreign ) {
            $this->extend("DROP FOREIGN KEY %id", $index->cname);
        }
        else if ( $index->primary ) {
            $this->extend("DROP PRIMARY KEY");
        }
        else {
            $this->reindent("--  ");
            $this->partial("WAS ");
            $this->reindent();
            $this->create_index($index);
            $this->extend("DROP KEY %id", $index->name);
        }
        return $this;
    }

    function ignore_index( $index ) {
        if ( $index instanceOf Modyllic_Index_Foreign and $index->weak ) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    function create_index( $index, $prefix=null ) {
        if ( $this->ignore_index( $index ) ) {
            return;
        }
        if ( isset($prefix) ) {
            $this->partial($prefix);
        }
        $this->extend();
        if ( $index instanceOf Modyllic_Index_Foreign ) {
            if ( $index->cname ) {
                $this->add( "CONSTRAINT %id ", $index->cname );
            }
            $this->add( "FOREIGN KEY " );
        }
        else if ( $index->primary ) {
            $this->add( "PRIMARY KEY " );
        }
        else if ( $index->unique ) {
            $this->add( "UNIQUE KEY " );
        }
        else if ( $index->fulltext ) {
            $this->add( "FULLTEXT KEY " );
        }
        else if ( $index->spatial ) {
            $this->add( "SPATIAL KEY " );
        }
        else {
            $this->add( "KEY " );
        }
        if ( !$index->primary and $index->name ) {
            $this->add( "%id ", $index->name );
        }
        $this->add( "(" );
        $num = 0;
        foreach ($index->columns as $name=>$length) {
            if ( $num++ ) {
                $this->add( "," );
            }
            $this->add( "%id", $name );
            if ( $length !== FALSE ) {
                $this->add( "(%lit)", $length );
            }
        }
        $this->add( ")" );
        if ( $index instanceOf Modyllic_Index_Foreign ) {
            $this->foreign_key( $index );
        }
        if ( isset($index->using) ) {
            $this->add( " USING %lit", $index->using );
        }
        return $this;
    }

    function foreign_key($index) {
        $this->add( " REFERENCES %id", $index->references['table'] );
        $this->add( " (" . implode(",",array_map(array("Modyllic_SQL","quote_ident"),array_map("trim",
                            $index->references['columns'] ))) .")" );
        if ( $index->references['on_delete'] ) {
           $this->add( " ON DELETE %lit", $index->references['on_delete'] );
        }
        if ( $index->references['on_update'] ) {
           $this->add( " ON UPDATE %lit", $index->references['on_update'] );
        }
    }

    function table_options($table,$schema=null) {
        if ( isset( $table->engine ) ) {
            $this->extend( "ENGINE=".$table->engine );
        }
        if ( isset($table->charset) and (!isset($schema) or $table->charset!=$schema->charset) ) {
            $this->extend( "DEFAULT CHARACTER SET=".$table->charset );
        }
        if ( isset($table->collate) and (!isset($schema) or $table->collate!=$schema->collate) ) {
            $this->extend( "DEFAULT COLLATE=".$table->collate );
        }
        return $this;
    }

    function create_data($table, $row) {
        $this->begin_cmd();
        $this->partial( "INSERT INTO %id SET ", $table->name );
        $this->begin_list();
        foreach ($row as $col=>$val) {
            $this->next_list_item();
            $this->partial( "%id=%lit", $col, $val );
        }
        $this->end_list();
        $this->end_cmd();
        return $this;
    }

    function create_table_data($table) {
        if ( $table->static ) {
            $this->cmd( "TRUNCATE TABLE %id", $table->name );
            foreach ($table->data as $row) {
                $this->create_data( $table, $row );
            }
        }
        return $this;
    }

    function table_docs($table) {
        if ( $table->docs ) {
            $this->extend( "-- " . implode("\n-- ", explode( "\n", $table->docs ) ) );
        }
        return $this;
    }

// VIEW

    function create_view( $view ) {
        $this->cmd( "CREATE VIEW %id %lit", $view->name, $view->def );
        return $this;
    }

    function alter_view( $view ) {
        $this->drop_view($view->from);
        $this->create_view($view);
        return $this;
    }

    function drop_view( $view ) {
        $this->cmd( "DROP VIEW %id", $view->name );
        return $this;
    }

// ROUTINE

    function routine_meta($routine) {
        $meta = array();
        if ( $routine->args_type != Modyllic_Routine::ARGS_TYPE_DEFAULT ) {
            $meta["args_type"] = $routine->args_type;
        }
        if ( $routine instanceOf Modyllic_Proc ) {
            if ( $routine->returns["type"] != Modyllic_Proc::RETURNS_TYPE_DEFAULT ) {
                $meta["returns"] = $routine->returns;
            }
        }
        if ( $routine->txns != Modyllic_Routine::TXNS_DEFAULT ) {
            $meta["txns"] = $routine->txns;
        }
        return $meta;
    }

    function create_routine( $routine, $dometa=TRUE ) {
        if ( $routine instanceOf Modyllic_Func ) {
            $this->create_function( $routine );
        }
        else if ($routine instanceOf Modyllic_Proc ) {
            $this->create_procedure( $routine );
        }
        else {
            throw new Exception("Don't know how to create ".get_class($routine));
        }
        if ( $dometa ) {
            $this->insert_meta("ROUTINE",$routine->name, $this->routine_meta($routine) );
        }
        return $this;
    }

    function alter_routine( $routine ) {
        $this->drop_routine( $routine->from, FALSE );
        $this->create_routine( $routine, FALSE );
        $frommeta = $this->routine_meta($routine->from);
        $tometa = $this->routine_meta($routine);
        if ( $frommeta != $tometa ) {
            $this->update_meta("ROUTINE",$routine->name,$tometa);
        }
        return $this;
    }

    function drop_routine( $routine, $dometa=TRUE ) {
        if ( $routine instanceOf Modyllic_Func ) {
            $this->drop_function( $routine );
        }
        else if ($routine instanceOf Modyllic_Proc ) {
            $this->drop_procedure( $routine );
        }
        else {
            throw new Exception("Don't know how to drop ".get_class($routine));
        }
        if ( $dometa ) {
            $this->delete_meta("ROUTINE",$routine->name );
        }
        return $this;
    }

    function routine_attrs( $routine ) {
        if ( $routine->access != Modyllic_Routine::ACCESS_DEFAULT ) {
            $this->extend( $routine->access );
        }
        if ( $routine->deterministic != Modyllic_Routine::DETERMINISTIC_DEFAULT ) {
            $this->extend( $routine->deterministic ? "DETERMINISTIC" : "NOT DETERMINISTIC" );
        }
        return $this;
    }

    function routine_body( $routine ) {
        $this->extend( $routine->body );
        return $this;
    }

    function routine_docs( $routine ) {
        if ( $routine->docs ) {
            $this->extend( "-- " . implode("\n-- ", explode( "\n", $routine->docs ) ) );
        }
        return $this;
    }
    function routine_diffs( $routine ) {
        if ( $routine->from and count($routine->args) != count($routine->from->args) ) {
            $this->extend( "-- Arguments count differ" );
        }
        if ( $routine->from and $routine->from->_body_no_comments() != $routine->_body_no_comments() ) {
            $from = "/tmp/sqlg_".getmypid()."_from.txt";
            $to   = "/tmp/sqlg_".getmypid()."_to.txt";
            file_put_contents($from,$routine->from->body);
            file_put_contents($to,$routine->body);
            $this->extend( "-- diff -u $from $to" );
            exec("diff -BE -u $from $to", $diff);
            if ( count($diff) == 0 ) {
                file_put_contents("/tmp/from.txt", $routine->from->_body_no_comments() );
                file_put_contents("/tmp/to.txt", $routine->_body_no_comments() );
                die( "Fake difference\n" );
            }
            $this->extend( "-- BODY DIFFERS (".count($diff)."):" );
            foreach ($diff as $line) {
                $this->extend( "-- $line" );
            }
            unlink( $from );
            unlink( $to );
        }
        return $this;
    }

    function routine_arg( $arg, $indent="" ) {
        if ( $arg->dir != "IN" ) {
            $dir = $arg->dir . " ";
        }
        else {
            $dir = "";
        }
        $this->extend( "%lit%id %lit", $dir, $arg->name, $arg->type->toSql() );
        return $this;
    }

    function routine_args( $routine ) {
        $argc = count($routine->args);
        if ( ! $argc ) {
            $this->add(")");
            return;
        }
        $this->indent();
        for ($ii=0; $ii<$argc; ++$ii ) {
            $arg = $routine->args[$ii];
            if ( $routine->from and isset($routine->from->args[$ii]) and ! $arg->equalTo($routine->from->args[$ii]) ) {
                $this->reindent("--  ");
                $this->routine_arg( $routine->from->args[$ii] );
                $this->reindent();
            }
            $this->routine_arg( $arg );
            if ( $ii < ($argc-1) ) {
                $this->add(",");
            }
            else {
                $this->add(")");
            }
            if ( $arg->docs ) {
                $this->add( " -- ".$arg->docs );
            }
        }
        $this->undent();
        return $this;
    }

// FUNCTION

    function create_function( $func ) {
        $this->begin_cmd();
        $this->routine_docs( $func );
        $this->routine_diffs( $func );
        $this->create_function_name($func);
        $this->routine_args($func);
        $this->function_returns( $func );
        $this->routine_attrs( $func );
        $this->routine_body( $func );
        $this->end_cmd();
        return $this;
    }

    function function_returns( $func ) {
        $this->extend( "RETURNS %lit", $func->returns->toSql() );
        return $this;
    }

    function create_function_name( $func ) {
        $this->extend( "CREATE FUNCTION %id(", $func->name );
        return $this;
    }
    function drop_function( $func ) {
        $this->cmd( "DROP FUNCTION %id", $func->name );
        return $this;
    }


// PROCEDURE

    function create_procedure( $proc ) {
        $this->begin_cmd();
        $this->routine_docs( $proc );
        $this->routine_diffs( $proc );
        $this->create_procedure_name($proc);
        $this->routine_args($proc);
        $this->procedure_returns( $proc );
        $this->routine_attrs( $proc );
        $this->routine_body( $proc );
        $this->end_cmd();

        return $this;
    }

    function create_procedure_name( $proc ) {
        $this->extend( "CREATE PROCEDURE %id(", $proc->name );
        return $this;
    }

    function procedure_returns( $proc ) {
        return $this;
    }

    function drop_procedure( $proc ) {
        $this->cmd( "DROP PROCEDURE IF EXISTS %id", $proc->name );
        return $this;
    }

// EVENT

    function create_event( $event ) {
        $this->begin_cmd( "CREATE EVENT %id", $event->name );
        $this->extend( "ON SCHEDULE %lit", $event->schedule );
        if ( $event->preserve ) {
            $this->extend( "ON COMPLETION PRESERVE" );
        }
        $this->extend( "%lit", $event->status );
        $this->extend( "DO %lit", $event->body );
        $this->end_cmd();
        if ($event->status == "DISABLE ON SLAVE") {
            $this->cmd( "ALTER EVENT %id ENABLE", $event->name );
        }
        return $this;
    }

    function alter_event( $event ) {
        $this->begin_cmd( "ALTER EVENT %id", $event->name );
        if ( isset($event->schedule) ) {
            $this->extend( "ON SCHEDULE %lit", $event->schedule );
        }
        if ( isset($event->preserve) ) {
            if ( $event->preserve ) {
                $this->extend( "ON COMPLETION PRESERVE" );
            }
            else {
                $this->extend( "ON COMPLETION NOT PRESERVE" );
            }
        }
        if ( isset($event->status) ) {
            $this->extend( $event->status );
        }
        if ( isset($event->body) ) {
            $this->extend( "DO %lit", $event->body );
        }
        $this->end_cmd();
        if (isset($event->status) and $event->status == "DISABLE ON SLAVE") {
            $this->cmd( "ALTER EVENT %id ENABLE", $event->name );
        }
        return $this;
    }

    function drop_event( $event ) {
        $this->cmd( "DROP EVENT %id", $event->name );
        return $this;
    }

// Helpers for data that we store that MySQL doesn't know how to store directly.

    function insert_meta($kind,$which,array $what) {
        if ( count($what) > 0 ) {
            $this->cmd( "INSERT INTO SQLMETA (kind,which,value) VALUES (%str, %str, %str)",
                $kind, $which, json_encode($what) );
        }
    }

    function delete_meta($kind,$which) {
        $this->cmd( "DELETE FROM SQLMETA WHERE kind=%str AND which=%str",
            $kind, $which );
    }

    function update_meta($kind,$which,array $what) {
        $this->delete_meta($kind,$which);
        if ( count($what) > 0 ) {
            $this->insert_meta($kind,$which,$what);
        }
    }



// SQL document wrapper

    function sql_document($delim=";", $sep=FALSE) {
        $sql = implode(";\n",$this->sql_header()) . ";\n";
        if ( $delim != ";" ) {
            $sql .= "DELIMITER $delim\n";
        }
        $sql .= "\n";
        $sql .= $this->sql_dump($delim,$sep);
        $sql .= "\n";
        if ( $delim != ";" ) {
            $sql .= "DELIMITER ;\n";
        }
        $sql .= implode(";\n",$this->sql_footer()) . ";\n";
        return $sql;
    }

    function sql_dump($delim=";", $sep=FALSE) {
        $sql = "";
        if ( $delim != "\n" ) {
            if ( $sep ) {
                $delim = "\n$delim";
            }
            $delim .= "\n";
        }
        $sql .= implode( $delim, $this->commands ) . $delim;
        return $sql;
    }

    function sql_commands() {
        return $this->commands;
    }

    function sql_header() {
        return array(
            "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0",
            $this->_format("SET NAMES %str",array("utf8")),
            );
    }

    function sql_footer() {
        return array( "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS" );
    }

// Code generation utilities

    protected $buffer;
    protected $commands = array();
    protected $indent_by = 4;
    protected $indent_with = " ";
    protected $indent = "";
    protected $level = 0;
    protected $cmd_level = 0;
    protected $filters = array();
    protected $partial = FALSE;
    protected $in_list = FALSE;
    protected $list_sep;
    protected $list_elem = 0;

    protected function clear_commands() {
        $this->commands = array();
        return $this;
    }

    protected function push_filter($filter) {
        array_unshift($this->filters,$filter);
        return $this;
    }

    protected function pop_filter() {
        array_shift($this->filters);
        return $this;
    }

    protected function add($str, $args=null) {
        if ( !is_array($args) ) {
            $args = func_get_args();
            array_shift($args); // Remove $str from the arg list
        }
        if ( count($args) ) {
            $str = $this->_format( $str, $args );
        }
        foreach ($this->filters as $filter) {
            $str = call_user_func($filter,$str);
        }
        $this->buffer .= $str;
        return $this;
    }

    protected function indent() {
        $this->level ++;
        $this->_generate_indent();
        return $this;
    }

    protected function undent() {
        if ( $this->level == 0 ) {
            throw new Exception("Can't undent past the beginning of the line");
        }
        $this->level --;
        $this->_generate_indent();
        return $this;
    }

    protected function _generate_indent() {
        $this->indent = str_repeat( $this->indent_with, $this->indent_by * $this->level );
        return $this;
    }

    protected function reindent( $indent=null, $partial=false ) {
        if ( $indent ) {
            $this->partial = $partial;
            $this->undent();
            $this->indent .= $indent;
        }
        else {
            $this->indent();
        }
        return $this;
    }

    protected $_format_args;
    protected function _format( $str, array $args ) {
        $this->_format_args = $args;
        $formatted = preg_replace_callback( '/%(id|str|lit)/', array($this,'_format_replace'), $str );
        unset($this->_format_args);
        return $formatted;
        return $this;
    }

    protected function _format_replace( $matches ) {
        switch ($matches[1]) {
            case 'id':
                $result = Modyllic_SQL::quote_ident(array_shift($this->_format_args));
                break;
            case 'str':
                $result = Modyllic_SQL::quote_str(array_shift($this->_format_args));
                break;
            case 'lit':
                $result = array_shift($this->_format_args);
                break;
            default:
                $result = '%'.$matches[1];
        }
        return $result;
        return $this;
    }

    protected function cmd($str, $args=null) {
        if ( !is_array($args) ) {
            $args = func_get_args();
            array_shift($args); // Remove $str from the arg list
        }
        $this->begin_cmd( $str, $args );
        $this->end_cmd();
        return $this;
    }

    protected function begin_cmd($str="", $args=null) {
        $this->cmd_level = $this->level;
        if ( $this->buffer != "" ) {
            $this->add("\n");
        }
        $this->add( $this->indent );
        if ( $str != "" ) {
            if ( !is_array($args) ) {
                $args = func_get_args();
                array_shift($args); // Remove $str from the arg list
            }
            $this->add( $this->_format( $str, $args ) );
            $this->indent();
        }
        return $this;
    }

    protected function end_cmd($str="", $args=null) {
        if ( $str != "" ) {
            if ( !is_array($args) ) {
                $args = func_get_args();
                array_shift($args); // Remove $str from the arg list
            }
            $this->add($str, $args);
        }
        if ( $this->level > $this->cmd_level ) {
            $this->undent();
        }
        $this->commands[] = $this->buffer;
        $this->buffer = "";
        $this->level = 0;
        if ( $this->in_list ) {
            $this->end_list();
        }
        $this->_generate_indent();
        return $this;
    }

    protected function begin_list($list_sep=", ") {
        $this->in_list = TRUE;
        $this->list_sep = $list_sep;
        $this->list_elem = 0;
        return $this;
    }

    protected function end_list() {
        $this->in_list = FALSE;
        unset($this->list_sep);
        $this->list_elem = 0;
        return $this;
    }

    protected function extend($str="", $args=null) {
        if ( ! $this->partial ) {
            $this->next_list_item();
        }
        if ( $this->buffer != "" and ! $this->partial ) {
            $this->add("\n");
        }
        if ( ! $this->partial ) {
            $this->add( $this->indent );
        }
        if ( !is_array($args) ) {
            $args = func_get_args();
            array_shift($args); // Remove $str from the arg list
        }
        $this->add($str, $args);
        $this->partial = FALSE;
        return $this;
    }

    protected function next_list_item() {
        if ( ! $this->in_list ) {
            return;
        }
        if ( $this->list_elem ++ ) {
            $this->add( $this->list_sep );
        }
        return $this;
    }

    protected function partial( $str, $args=array() ) {
        if ( !is_array($args) ) {
            $args = func_get_args();
            array_shift($args); // Remove $str from the arg list
        }
        $this->extend($str,$args);
        $this->partial = TRUE;
        return $this;
    }

}

