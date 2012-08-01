<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator_MySQL {

    protected $delim;
    protected $sep;
    protected $what;
    protected $source;
    protected $from_sqlmeta_exists;
    protected $to_sqlmeta_exists;

    function __construct( $delim=';;', $sep=true ) {
        $this->set_what( $this->schema_types() );
        $this->delim = $delim;
        $this->sep = $sep;
    }

    function sqlmeta_exists(Modyllic_Schema $schema) {
        foreach ($schema->tables as $table) {
            if ($this->table_sqlmeta_exists($table)) {
                return true;
            }
        }
        foreach ($schema->routines as $routine) {
            if ($this->routine_sqlmeta_exists($routine)) {
                return true;
            }
        }
        return false;
    }

    function table_sqlmeta_exists(Modyllic_Schema_Table $table) {
        if ( count($this->table_meta($table)) ) {
            return true;
        }
        foreach ($table->columns as $column) {
            if ( count($this->column_meta($column)) ) {
                return true;
            }
        }
        foreach ($table->indexes as $index) {
            if ( count($this->index_meta($index)) ) {
                return true;
            }
        }
    }

    function routine_sqlmeta_exists($routine) {
        if ( count($this->routine_meta($routine)) ) {
            return true;
        }
        foreach ($routine->args as $arg) {
            if (count($this->routine_arg_meta($arg))) {
                return true;
            }
        }
    }

    function set_what($what) {
        $this->what = $what;
        $this->validate_schema_types($what);
        foreach ($what as $type) {
            $this->what[$type] = true;
        }
    }

    function schema_types() {
        return array('database','sqlmeta','tables','views','routines','events','triggers');
    }

    function validate_schema_types(array $what) {
        $diff = array_diff($what, $this->schema_types());
        if ( count($diff) ) {
            throw new Exception("Unknown kind of SQL schema element: ".implode(", ",$diff));
        }
    }

// ALTER

    function alter_sql( Modyllic_Diff $diff ) {
        $this->alter( $diff );
        return $this->sql_document( $this->delim, $this->sep );
    }

    function alter( Modyllic_Diff $diff ) {
        $this->from_sqlmeta_exists = $this->sqlmeta_exists($diff->from);
        $this->to_sqlmeta_exists = $this->sqlmeta_exists($diff->to);

        $this->source = $diff->changeset;
        if ( ! $diff->changeset->has_changes() ) {
            $this->cmd("-- No changes detected.");
            return $this;
        }
        if ( isset($this->what['database']) ) {
            $this->alter_database( $diff->changeset->schema );
        }
        if ( isset($this->what['sqlmeta']) ) {
            if ( $this->to_sqlmeta_exists and ! $this->from_sqlmeta_exists ) {
                $this->create_sqlmeta();
            }
        }

        if ( isset($this->what['triggers']) ) {
            $this->drop_triggers( $diff->changeset->remove['triggers'] );
        }
        if ( isset($this->what['events']) ) {
            $this->drop_events( $diff->changeset->remove['events'] );
        }
        if ( isset($this->what['routines']) ) {
            $this->drop_routines( $diff->changeset->remove['routines'] );
        }
        if ( isset($this->what['views']) ) {
            $this->drop_views( $diff->changeset->remove['views'] );
        }
        if ( isset($this->what['tables']) ) {
            $this->drop_tables( $diff->changeset->remove['tables'] );
        }

        if ( isset($this->what['tables']) ) {
            $this->create_tables( $diff->changeset->add['tables'], $diff->changeset->schema );
        }
        if ( isset($this->what['views']) ) {
            $this->create_views( $diff->changeset->add['views'] );
        }
        if ( isset($this->what['routines']) ) {
            $this->create_routines( $diff->changeset->add['routines'] );
        }
        if ( isset($this->what['events']) ) {
            $this->create_events( $diff->changeset->add['events'] );
        }
        if ( isset($this->what['triggers']) ) {
            $this->create_triggers( $diff->changeset->add['triggers'] );
        }

        if ( isset($this->what['tables']) ) {
            $this->alter_tables( $diff->changeset->update['tables'] );
        }
        if ( isset($this->what['views']) ) {
            $this->alter_views( $diff->changeset->update['views'] );
        }
        if ( isset($this->what['routines']) ) {
            $this->alter_routines( $diff->changeset->update['routines'] );
        }
        if ( isset($this->what['events']) ) {
            $this->alter_events( $diff->changeset->update['events'] );
        }
        if ( isset($this->what['triggers']) ) {
            $this->alter_triggers( $diff->changeset->update['triggers'] );
        }
        if ( isset($this->what['sqlmeta']) ) {
            if ( $this->from_sqlmeta_exists and ! $this->to_sqlmeta_exists ) {
                $this->drop_sqlmeta();
            }
        }
        $this->source = null;
        return $this;
    }

// CREATE

    function create_sql( Modyllic_Schema $schema ) {
        $this->create( $schema );
        return $this->sql_document( $this->delim, $this->sep );
    }

    function create( Modyllic_Schema $schema) {
        $this->source = $schema;
        $this->to_sqlmeta_exists = $this->sqlmeta_exists($schema);
        if ( isset($this->what['database']) ) {
            $this->create_database( $schema );
        }
        if ( isset($this->what['sqlmeta']) and $this->to_sqlmeta_exists ) {
            $this->create_sqlmeta();
        }
        if ( isset($this->what['tables']) ) {
            $this->create_tables( $schema->tables, $schema );
        }
        if ( isset($this->what['views']) ) {
            $this->create_views( $schema->views );
        }
        if ( isset($this->what['routines']) ) {
            $this->create_routines( $schema->routines );
        }
        if ( isset($this->what['events']) ) {
            $this->create_events( $schema->events );
        }
        if ( isset($this->what['triggers']) ) {
            $this->create_triggers( $schema->triggers );
        }
        $this->source = null;
        return $this;
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


// DROP

    function drop_sql( Modyllic_Schema $schema ) {
        $this->drop($schema);
        return $this->sql_document( $this->delim, $this->sep );
    }

    function drop( Modyllic_Schema $schema ) {
        $this->source = $schema;
        $this->to_sqlmeta_exists = $this->sqlmeta_exists($schema);
        if ( isset($this->what['triggers']) ) {
            $this->drop_triggers( $schema->triggers );
        }
        if ( isset($this->what['events']) ) {
            $this->drop_events( $schema->events );
        }
        if ( isset($this->what['routines']) ) {
            $this->drop_routines( $schema->routines );
        }
        if ( isset($this->what['views']) ) {
            $this->drop_views( $schema->views );
        }
        if ( isset($this->what['tables']) ) {
            $this->drop_tables( $schema->tables );
        }
        if ( isset($this->what['sqlmeta']) and $this->to_sqlmeta_exists ) {
            $this->drop_sqlmeta();
        }
        if ( isset($this->what['database']) ) {
            $this->drop_database( $schema );
        }
        $this->source = null;
        return $this;
    }

    function drop_sqlmeta() {
        $this->cmd('DROP TABLE IF EXISTS SQLMETA');
    }

// DATABASE

    function create_database($schema) {
        if ($schema->name_is_default) return $this;
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
        if ($schema->name_is_default) return $this;
        $this->cmd( "DROP DATABASE IF EXISTS %id", $schema->name );
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

// TRIGGERS

    function create_triggers( $triggers ) {
        foreach ( $triggers as $trigger ) {
            $this->create_trigger($trigger);
        }
        return $this;
    }

    function alter_triggers( $triggers ) {
        foreach ( $triggers as $trigger ) {
            $this->alter_trigger($trigger);
        }
        return $this;
    }

    function drop_triggers( $triggers ) {
        foreach ( array_reverse($triggers) as $trigger ) {
            $this->drop_trigger($trigger);
        }
        return $this;
    }

// TABLE

    function table_meta($table) {
        if ( $table->static != Modyllic_Schema_Table::STATIC_DEFAULT ) {
            return array( "static"=>$table->static );
        }
        else {
            return array();
        }
    }

    function create_table( Modyllic_Schema_Table $table, $schema ) {
        $this->begin_cmd();
        $this->table_docs( $table );
        $this->extend( "CREATE TABLE %id (", $table->name );
        $this->indent();
        $indexes = array_filter( $table->indexes, array($this,"active_index_filter") );
        $entries = count($table->columns) + count($indexes);
        $completed = 0;
        foreach ( $table->columns as $column ) {
            $this->create_column( $column );
            if ( $column->is_primary ) {
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

            if ( isset($table->static) ) {
                if ( $table->static ) {
                    $this->insert_meta( "TABLE", $table->name, $this->table_meta($table) );
                }
                else {
                    $this->delete_meta( "TABLE", $table->name );
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

        if ( isset($table->static) and $table->static and ! $table->from->static ) {
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

    function drop_table( Modyllic_Schema_Table $table ) {
        $this->cmd( "DROP TABLE IF EXISTS %id", $table->name );
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

    function column_meta( Modyllic_Schema_Column $col) {
        $meta = array();
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

    function add_column( Modyllic_Schema_Column $column ) {
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
            $this->create_column( $column->from, false );
            $this->reindent();
        }
        if ( $column->previously != $column->name ) {
            $this->partial( "CHANGE COLUMN %id ", $column->previously );
        }
        else {
            $this->partial( "MODIFY COLUMN " );
        }
        $this->create_column($column, false);
        return $this;
    }

    function drop_column( $column ) {
        $this->extend("DROP COLUMN %id", $column->name);
        return $this;
    }

    function create_column( Modyllic_Schema_Column $column, $with_key=true ) {
        if ( isset($column->from) ) {
            $this->extend("%id %lit", $column->name, $column->type->to_sql($column->from->type) );
        }
        else {
            $this->extend("%id %lit", $column->name, $column->type->to_sql() );
        }
        if ( ! $column->type instanceOf Modyllic_Type_Serial ) {
            if ( ! $column->null ) {
                $this->add( " NOT NULL" );
            }
            if ( $column->auto_increment ) {
                $this->add( " auto_increment" );
            }
        }
        if ( ! is_null($column->default) ) {
            if ( !$column->null or $column->default!='NULL' ) {
                $this->add( " DEFAULT %lit", $column->default );
            }
        }
        if ( $column->on_update ) {
            $this->add( " ON UPDATE %lit", $column->on_update );
        }
        if ( $with_key and $column->is_primary ) {
            $this->add( " PRIMARY KEY" );
        }
        if ( $with_key and $column->unique and ! $column->type instanceOf Modyllic_Type_Serial ) {
            $this->add( " UNIQUE" );
        }
        return $this;
    }

    function index_meta($index) {
        $meta = array();
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

    function add_index( $index ) {
        $this->create_index($index, "ADD ");
        return $this;
    }

    function drop_index( $index ) {
        if ( $index instanceOf Modyllic_Schema_Index_Foreign ) {
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

    function active_index_filter($index) {
        return ! $this->ignore_index($index);
    }

    function ignore_index(Modyllic_Schema_Index $index ) {
        if ( $index instanceOf Modyllic_Schema_Index_Foreign and $index->weak ) {
            return true;
        }
        # Indexes associated with columns definitions will be created by
        # that column definition.
        else if ( $index->column_defined ) {
            return true;
        }
        else {
            return false;
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
        if ( $index instanceOf Modyllic_Schema_Index_Foreign ) {
            if ( ! $index->dynamic_name and $index->cname ) {
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
        if ( !$index->primary and !$index->dynamic_name and $index->name ) {
            $this->add( "%id ", $index->name );
        }
        $this->add( "(" );
        $num = 0;
        foreach ($index->columns as $name=>$length) {
            if ( $num++ ) {
                $this->add( "," );
            }
            $this->add( "%id", $name );
            if ( $length !== false ) {
                $this->add( "(%lit)", $length );
            }
        }
        $this->add( ")" );
        if ( $index instanceOf Modyllic_Schema_Index_Foreign ) {
            $this->foreign_key( $index );
        }
        if ( isset($index->using) ) {
            $this->add( " USING %lit", $index->using );
        }
        return $this;
    }

    function foreign_key(Modyllic_Schema_Index $index) {
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
        $this->cmd( "DROP VIEW IF EXISTS %id", $view->name );
        return $this;
    }

// ROUTINE

    function routine_meta($routine) {
        $meta = array();
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

    function create_routine( $routine, $dometa=true ) {
        if ( $routine instanceOf Modyllic_Schema_Func ) {
            $this->create_function( $routine );
        }
        else if ($routine instanceOf Modyllic_Schema_Proc ) {
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
        $this->drop_routine( $routine->from, false );
        $this->create_routine( $routine, false );
        $frommeta = $this->routine_meta($routine->from);
        $tometa = $this->routine_meta($routine);
        if ( $frommeta != $tometa ) {
            $this->update_meta("ROUTINE",$routine->name,$tometa);
        }
        return $this;
    }

    function drop_routine( $routine, $dometa=true ) {
        if ( $routine instanceOf Modyllic_Schema_Func ) {
            $this->drop_function( $routine );
        }
        else if ($routine instanceOf Modyllic_Schema_Proc ) {
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
        if ( $routine->access != Modyllic_Schema_Routine::ACCESS_DEFAULT ) {
            $this->extend( $routine->access );
        }
        if ( $routine->deterministic != Modyllic_Schema_Routine::DETERMINISTIC_DEFAULT ) {
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

    function routine_arg_meta( Modyllic_Schema_Arg $arg ) {
        $meta = array();
        if ( $arg->type instanceOf Modyllic_Type_Boolean ) {
            $meta["type"] = "BOOLEAN";
        }
        else if ( $arg->type instanceOf Modyllic_Type_Serial ) {
            $meta["type"] = "SERIAL";
        }
        return $meta;
    }

    function routine_arg( $routine, Modyllic_Schema_Arg $arg, $indent="" ) {
        if ( $arg->dir != "IN" ) {
            $dir = $arg->dir . " ";
        }
        else {
            $dir = "";
        }
        $this->extend( "%lit%id %lit", $dir, $arg->name, $arg->type->to_sql() );
        $this->insert_meta( "ARG", $routine->name . "." . $arg->name, $this->routine_arg_meta($arg) );
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
            if ( $routine->from and isset($routine->from->args[$ii]) and ! $arg->equal_to($routine->from->args[$ii]) ) {
                $this->reindent("--  ");
                $this->routine_arg( $routine, $routine->from->args[$ii] );
                $this->reindent();
            }
            $this->routine_arg( $routine, $arg );
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
        $this->extend( "RETURNS %lit", $func->returns->to_sql() );
        return $this;
    }

    function create_function_name( $func ) {
        $this->extend( "CREATE FUNCTION %id(", $func->name );
        return $this;
    }
    function drop_function( $func ) {
        $this->cmd( "DROP FUNCTION IF EXISTS %id", $func->name );
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

// TRIGGER

    function create_trigger( $trigger ) {
        $this->begin_cmd( "CREATE TRIGGER %id", $trigger->name );
        $this->undent();
        $this->extend( "%lit %lit ON %id", $trigger->time, $trigger->event, $trigger->table );
        $this->extend( "FOR EACH ROW %lit", $trigger->body );
        $this->end_cmd();
        return $this;
    }

    function alter_trigger( $trigger ) {
        $this->drop_trigger( $trigger->from );
        $this->create_trigger( $trigger );
    }

    function drop_trigger( $trigger ) {
        $this->cmd( "DROP TRIGGER IF EXISTS %id", $trigger->name );
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
        $this->cmd( "DROP EVENT IF EXISTS %id", $event->name );
        return $this;
    }

// Helpers for data that we store that MySQL doesn't know how to store directly.

    function insert_meta($kind,$which,array $meta) {
        if ( count($meta) > 0 ) {
            if ( ! isset($this->what['sqlmeta']) ) { return; }
            $this->cmd( "INSERT INTO SQLMETA (kind,which,value) VALUES (%str, %str, %str)",
                $kind, $which, json_encode($meta) );
        }
    }

    function delete_meta($kind,$which) {
        if ( ! isset($this->what['sqlmeta']) ) { return; }
        if ( ! $this->to_sqlmeta_exists ) { return; }
        $this->cmd( "DELETE FROM SQLMETA WHERE kind=%str AND which=%str",
            $kind, $which );
    }

    function update_meta($kind,$which,array $meta) {
        if ( ! isset($this->what['sqlmeta']) ) { return; }
        if ( count($meta) > 0 ) {
            $meta_str = json_encode($meta);
            $this->cmd( "INSERT INTO SQLMETA SET kind=%str, which=%str, value=%str ON DUPLICATE KEY UPDATE value=%str",
                 $kind, $which, $meta_str, $meta_str );
        }
        else {
            if ( ! $this->to_sqlmeta_exists ) { return; }
            $this->delete_meta($kind,$which);
        }
    }



// SQL document wrapper

    function sql_document($delim=";", $sep=false) {
        $sql = "";
        if ( $sql_header = $this->sql_header() ) {
           $sql .= implode(";\n", $sql_header) . ";\n";
        }
        if ( $delim != ";" ) {
            $sql .= "DELIMITER $delim\n";
        }
        $sql .= "\n";
        $sql .= $this->sql_dump($delim,$sep);
        $sql .= "\n";
        if ( $delim != ";" ) {
            $sql .= "DELIMITER ;\n";
        }
        if ( $sql_footer = $this->sql_footer() ) {
           $sql .= implode(";\n", $sql_footer) . ";\n";
        }
        return $sql;
    }

    function sql_dump($delim=";", $sep=false) {
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
    protected $partial = false;
    protected $in_list = false;
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
        $this->in_list = true;
        $this->list_sep = $list_sep;
        $this->list_elem = 0;
        return $this;
    }

    protected function end_list() {
        $this->in_list = false;
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
        $this->partial = false;
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
        $this->partial = true;
        return $this;
    }

}

