<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of SQL entities comprising a complete schema
 */
class Modyllic_Schema extends Modyllic_Diffable {
    public $tables = array();
    public $routines = array();
    public $views = array();
    public $events = array();
    public $triggers = array();
    const DEFAULT_NAME = "database";
    public $name = self::DEFAULT_NAME;
    public $name_is_default = true;
    const DEFAULT_CHARSET = "utf8";
    public $charset = self::DEFAULT_CHARSET;
    const DEFAULT_COLLATE = "utf8_general_ci";
    public $collate = self::DEFAULT_COLLATE;
    public $docs = "";
    public $source = "generated";

    function reset() {
        $this->triggers       = array();
        $this->routines       = array();
        $this->tables         = array();
        $this->views          = array();
        $this->events         = array();
        $this->name           = self::DEFAULT_NAME;
        $this->name_is_default  = true;
        $this->charset        = self::DEFAULT_CHARSET;
        $this->collate        = self::DEFAULT_COLLATE;
        $this->docs           = "";
        $this->source         = "generated";
    }

    function set_name( $name ) {
        $this->name_is_default = ( $name == self::DEFAULT_NAME );
        $this->name = $name;
    }

    function merge( Modyllic_Schema $schema ) {
        if ( $this->name_is_default ) {
            $this->set_name($schema->name);
        }
        if ( $this->charset == self::DEFAULT_CHARSET ) {
            $this->charset = $schema->charset;
        }
        if ( $this->collate == self::DEFAULT_COLLATE ) {
            $this->collate = $schema->collate;
        }
        if ( $this->docs == "" ) {
            $this->docs = $schema->docs;
        }
        foreach ($schema->tables as $table) {
            $this->add_table($table);
        }
        foreach ($schema->routines as $routine) {
            $this->add_routine($routine);
        }
        foreach ($schema->views as $view) {
            $this->add_view($view);
        }
        foreach ($schema->events as $event) {
            $this->add_event($event);
        }
        foreach ($schema->triggers as $trigger) {
            $this->add_trigger($trigger);
        }
    }

    /**
     * @param Modyllic_Schema_Table $table
     */
    function add_table(Modyllic_Schema_Table $table ) {
        $this->tables[$table->name] = $table;
        return $table;
    }

    /**
     * @param Modyllic_Schema_Routine $routine
     */
    function add_routine(Modyllic_Schema_Routine $routine ) {
        $this->routines[$routine->name] = $routine;
        return $routine;
    }

    /**
     * @param Modyllic_Schema_Event $event
     */
    function add_event(Modyllic_Schema_Event $event ) {
        $this->events[$event->name] = $event;
        return $event;
    }

    /**
     * @param Modyllic_Schema_Trigger $trigger
     */
    function add_trigger(Modyllic_Schema_Trigger $trigger ) {
        $this->triggers[$trigger->name] = $trigger;
        return $trigger;
    }

    /**
     * @param Modyllic_Schema_View $view
     */
    function add_view(Modyllic_Schema_View $view ) {
        $this->views[$view->name] = $view;
        return $view;
    }

    function unquote_sql_str($sql) {
        $tok = new Modyllic_Tokenizer( $sql );
        return $tok->next()->unquote();
    }

    /**
     * Generates a meta table entry that wasn't in the schema
     */
    function load_meta() {
        $metadata = null;
        # If we already have an metadata table then this is a load directly
        # from a database (or a dump from a database).  We'll want to
        # convert that back into our usual metadata.
        if ( isset($this->tables['MODYLLIC']) and isset($this->tables['MODYLLIC']->data) ) {
            $metadata = $this->tables['MODYLLIC']->data;
        }
        # @todo to be removed in 0.2.11+
        else if ( isset($this->tables['SQLMETA']) and isset($this->tables['SQLMETA']->data) ) {
            $metadata = $this->tables['SQLMETA']->data;
        }
        if ( $metadata ) {
            foreach ($metadata as &$row) {
                $kind = $this->unquote_sql_str($row['kind']);
                $which = $this->unquote_sql_str($row['which']);
                $meta = json_decode($this->unquote_sql_str($row['value']), true);
                $obj = null;
                switch ($kind) {
                    case 'TABLE':
                        if ( isset($this->tables[$which]) ) {
                            $obj = $this->tables[$which];
                        }
                        break;
                    case 'COLUMN':
                        list($table,$col) = explode(".",$which);
                        if ( isset($this->tables[$table]) and isset($this->tables[$table]->columns[$col]) ) {
                            $obj = $this->tables[$table]->columns[$col];
                        }
                        break;
                    case 'INDEX':
                        list($table,$index) = explode(".",$which);
                        if ( isset($this->tables[$table]) and isset($this->tables[$table]->indexes[$index]) ) {
                            $obj = $this->tables[$table]->indexes[$index];
                        }
                        break;
                    case 'ROUTINE':
                        if ( isset($this->routines[$which]) ) {
                            $obj = $this->routines[$which];
                        }
                        break;
                    case 'ARG':
                        list($routine,$arg) = explode(".",$which);
                        if ( isset($this->routines[$routine]) and isset($this->routines[$routine]->args[$arg]) ) {
                            $obj = $this->routines[$routine]->args[$arg];
                        }
                        break;
                    default:
                        throw new Exception("Unknown kind of metadata '$kind' found in the metadata table");
                        break;
                }
                if ( isset($obj) ) {
                    foreach ($meta as $metakey=>&$metavalue) {
                        # Types are always a type object, which we'll
                        # recreate via cloning if a different type spec is
                        # requested.  This allows us to round trip things
                        # like BOOLEAN and SERIAL.
                        if ( $metakey == "type" ) {
                            $new_type = Modyllic_Type::create($metavalue);
                            $new_type->copy_from( $obj->type );
                            $obj->type = $new_type;
                        }
                        else {
                            $obj->$metakey = $metavalue;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Modyllic_Schema $other
     */
    function schema_def_equal_to(Modyllic_Schema $other ) {
        if ( $this->charset != $other->charset ) { return false; }
        if ( $this->collate != $other->collate ) { return false; }
        return true;
    }

    function equal_to(Modyllic_Schema $other ) {
        if ( ! $this->schema_def_equal_to($other) ) { return false; }
        if ( count($this->tables) != count($other->tables) ) { return false; }
        if ( count($this->routines) != count($other->routines) ) { return false; }
        if ( count($this->events) != count($other->events) ) { return false; }
        if ( count($this->triggers) != count($other->triggers) ) { return false; }
        if ( count($this->views) != count($other->views) ) { return false; }
        foreach ($this->tables as $key=>&$table) {
            if ( ! $table->equal_to( $other->tables[$key] ) ) { return false; }
        }
        foreach ($this->routines as $key=>&$routine) {
            if ( ! $routine->equal_to( $other->routines[$key] ) ) { return false; }
        }
        foreach ($this->events as $key=>&$event) {
            if ( ! $event->equal_to( $other->events[$key] ) ) { return false; }
        }
        foreach ($this->views as $key=>&$view) {
            if ( ! $view->equal_to( $other->views[$key] ) ) { return false; }
        }
        return true;
    }
}
