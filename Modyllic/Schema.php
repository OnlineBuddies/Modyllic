<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/SQL.php";
require_once "Modyllic/Types.php";

/**
 * A base class for various schema objects.  Handles generic things like
 * providing previous values for the diff engine.  In a perfect world this
 * would be a runtime trait applied by the diff engine.
 */
class Modyllic_Diffable {
    public $from;
}

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
    public $nameIsDefault = true;
    const DEFAULT_CHARSET = "utf8";
    public $charset = self::DEFAULT_CHARSET;
    const DEFAULT_COLLATE = "utf8_general_ci";
    public $collate = self::DEFAULT_COLLATE;
    public $docs = "";
    
    function reset() {
        $this->triggers       = array();
        $this->routines       = array();
        $this->tables         = array();
        $this->views          = array();
        $this->events         = array();
        $this->name           = self::DEFAULT_NAME;
        $this->nameIsDefault  = true;
        $this->charset        = self::DEFAULT_CHARSET;
        $this->collate        = self::DEFAULT_COLLATE;
        $this->docs           = "";
    }
    
    function nameIsDefault() {
        return ($this->name == self::DEFAULT_NAME);
    }
    
    function setName( $name ) {
        $this->nameIsDefault = ( $name == self::DEFAULT_NAME );
        $this->name = $name;
    }

    function merge( $schema ) {
        if ( $this->nameIsDefault ) {
            $this->setName($schema->name);
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
     * @param Modyllic_Table $table
     */
    function add_table( $table ) {
        $this->tables[$table->name] = $table;
        return $table;
    }

    /**
     * @param Modyllic_Routine $routine
     */
    function add_routine( $routine ) {
        $this->routines[$routine->name] = $routine;
        return $routine;
    }

    /**
     * @param Modyllic_Event $event
     */
    function add_event( $event ) {
        $this->events[$event->name] = $event;
        return $event;
    }
    
    /**
     * @param Modyllic_Trigger $trigger
     */
    function add_trigger( $trigger ) {
        $this->triggers[$trigger->name] = $trigger;
        return $trigger;
    }

    /**
     * @param Modyllic_View $view
     */
    function add_view( $view ) {
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
    function load_sqlmeta() {
        # If we already have an SQLMETA table then this is a load directly
        # from a database (or a dump from a database).  We'll want to
        # convert that back into our usual metadata.
        if ( isset($this->tables['SQLMETA']) and isset($this->tables['SQLMETA']->data) ) {
            foreach ($this->tables['SQLMETA']->data as &$row) {
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
                    default:
                        throw new Exception("Unknown kind of metadata $kind found in SQLMETA");
                        break;
                }
                if ( isset($obj) ) {
                    foreach ($meta as $metakey=>&$metavalue) {
                        $obj->$metakey = $metavalue;
                    }
                }
            }
            unset($this->tables['SQLMETA']);
        }
    }

    /**
     * @param Modyllic_Schema $other
     */
    function schemaDefEqualTo( $other ) {
        if ( $this->charset != $other->charset ) { return false; }
        if ( $this->collate != $other->collate ) { return false; }
        return true;
    }

    function equalTo( $other ) {
        if ( ! $this->schemaDefEqualTo($other) ) { return false; }
        if ( count($this->tables) != count($other->tables) ) { return false; }
        if ( count($this->routines) != count($other->routines) ) { return false; }
        if ( count($this->events) != count($other->events) ) { return false; }
        if ( count($this->triggers) != count($other->triggers) ) { return false; }
        if ( count($this->views) != count($other->views) ) { return false; }
        foreach ($this->tables as $key=>&$table) {
            if ( ! $table->equalTo( $other->tables[$key] ) ) { return false; }
        }
        foreach ($this->routines as $key=>&$routine) {
            if ( ! $routine->equalTo( $other->routines[$key] ) ) { return false; }
        }
        foreach ($this->events as $key=>&$event) {
            if ( ! $event->equalTo( $other->events[$key] ) ) { return false; }
        }
        foreach ($this->views as $key=>&$view) {
            if ( ! $view->equalTo( $other->views[$key] ) ) { return false; }
        }
        return true;
    }
}

class Modyllic_View extends Modyllic_Diffable {
    public $name;
    public $def;
    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }
    function equalTo( $other ) {
        if ( $this->def != $other->def ) { return false; }
        return true;
    }
}

/**
 * A collection of columns, indexes and other information comprising a table
 */
class Modyllic_Table extends Modyllic_Diffable {
    public $name;
    public $columns = array();
    public $indexes = array();
    const STATIC_DEFAULT = false;
    public $static = self::STATIC_DEFAULT;
    public $data = array();
    public $last_column;
    public $last_index;
    public $engine = 'InnoDB';
    public $charset = 'utf8';
    public $collate = 'utf8_general_ci';
    public $docs = "";
    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }


    /**
     * @param Modyllic_Column $column
     */
    function add_column($column) {
        if ( isset($this->last_column) ) {
            $column->after = $this->last_column->name;
        }
        $this->last_column = $column;
        $this->columns[$column->name] = $column;
        return $column;
    }
    /**
     * @param Modyllic_Index $index
     */
    function add_index($index) {
        $name = $index->getName();
        if ( isset($this->indexes[$name]) ) {
            throw new Exception("In table ".$this->name."- duplicate key name ".$name);
        }
        $this->indexes[$name] = $index;
        $this->last_index = $index;
        foreach ($index->columns as $cname=>$value) {
            if ( ! isset($this->columns[$cname]) ) {
                throw new Exception("In table ".$this->name.", index $name, can't index unknown column $cname");
            }
        }
        // If this is a primary key and has only one column then we'll flag that column as a primary key
        if ($index->primary and count($index->columns) == 1) {
            $name = current( array_keys($index->columns) );
            $len = current( array_values($index->columns) );
            // And if there's no length limiter on the column...
            if ( $len === false ) {
                $this->columns[$name]->is_primary = true;
            }
        }
        return $index;
    }

    /**
     * @param string $prefix Get's an index name
     * @returns string
     */
    function gen_index_name( $prefix, $always_num=false ) {
        $num = 1;
        $name = $prefix . ($always_num? "_$num": "");
        while ( isset($this->indexes[$name]) or isset($this->indexes['~'.$name]) ) {
            $name = $prefix . "_" . ++$num;
        }
        return $name;
    }

    /**
     * @param Modyllic_Table $other
     * @returns bool True if $other is equivalent to $this
     */
    function equalTo( $other ) {
        if ( $this->name != $other->name ) { return false; }
        if ( $this->engine != $other->engine ) { return false; }
        if ( $this->charset != $other->charset ) { return false; }
        if ( $this->static != $other->static ) { return false; }
        if ( count($this->columns) != count($other->columns) ) { return false; }
        if ( count($this->indexes) != count($other->indexes) ) { return false; }
        foreach ( $this->columns as $key=>&$value ) {
            if ( ! $value->equalTo( $other->columns[$key] ) ) { return false; }
        }
        foreach ( $this->indexes as $key=>&$value ) {
            if ( ! $value->equalTo( $other->indexes[$key] ) ) { return false; }
        }
        return true;
    }

    /**
     * Clears the data associated with this table.  Also initializes it,
     * allowing data to be inserted into it.
     */
    function clear_data() {
        $this->data = array();
        $this->static = true;
    }

    /**
     * Add a row of data to this table
     * @throws Exception when data is not yet initialized.
     */
    function add_row( $row ) {
        if ( ! $this->static and $this->name != "SQLMETA" ) {
            throw new Exception("Cannot add data to ".$this->name.
                ", not initialized for schema supplied data-- call TRUNCATE first.");
        }
        foreach ($row as $col_name=>&$value) {
            if ( ! isset($this->columns[$col_name]) ) {
                throw "INSERT references $col_name in ".$this->name." but $col_name doesn't exist";
            }
            $col = $this->columns[$col_name];
            $norm_value = $col->type->normalize($value);
            $row[$col_name] = $norm_value;
        }
        $this->data[] = $row;
    }

    /**
     * Get the primary key.  If there is no primary key then we require all
     * columns to uniquely identify a row.
     * @returns array
     */
    function primary_key() {
        foreach ($this->indexes as &$index) {
            if ( $index->primary) {
                return $index->columns;
                break;
            }
        }
        $pk = array();
        foreach ($this->columns as $name=>&$col) {
            $pk[$name] = false;
        }
        return $pk;
    }

    /**
     * For a given row, return the key/value pairs needed to match it, based
     * on the primary key for this table.
     * @returns array
     */
    function match_row($row) {
        $where = array();
        foreach ($this->primary_key() as $key=>$len) {
             $where[$key] = @$row[$key];
        }
        return $where;
    }

}

/**
 * A collection of attributes describing a column in a table
 */
class Modyllic_Column extends Modyllic_Diffable {
    public $name;
    public $aliases = array();
    public $previously;
    public $type;
    public $null = true;
    public $default = "NULL";
    public $auto_increment = false;
    public $on_update;
    public $docs = "";
    public $after;
    public $is_primary;

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }


    /**
     * @param Modyllic_Column $other
     * @returns bool True if $other is equivalent to $this
     */
    function equalTo($other) {
        if ( $this->name != $other->name ) { return false; }
        if ( ! $this->type->equalTo( $other->type ) ) { return false; }
        if ( $this->null != $other->null ) { return false; }
        if ( $this->default != $other->default ) { return false; }
        if ( $this->auto_increment != $other->auto_increment ) { return false; }
        if ( $this->on_update != $other->on_update ) { return false; }
        if ( $this->aliases != $other->aliases ) { return false; }
        return true;
    }
}

/**
 * A collection of attributes describing an index on a table
 */
class Modyllic_Index extends Modyllic_Diffable {
    public $name  = "";
    public $docs = "";
    public $dynamic_name = false;
    public $spatial = false;
    public $primary = false;
    public $fulltext = false;
    public $unique   = false;
    public $using;
    public $columns  = array();

    /**
     * @param string $name
     */
    function __construct($name="") {
        $this->name = $name;
    }

    function getName() {
        return $this->name;
    }

    /**
     * @param Modyllic_Index $other
     * @returns bool True if $other is equivalent to $this
     */
    function equalTo($other) {
        if ( get_class($other) != get_class($this) )   { return false; }
        if ( $this->columns != $other->columns ) { return false; }
        if ( $this->primary != $other->primary ) { return false; }
        if ( $this->fulltext != $other->fulltext ) { return false; }
        if ( $this->unique != $other->unique ) { return false; }
        if ( $this->using != $other->using ) { return false; }
        if ( $this->spatial != $other->spatial ) { return false; }
        return true;
    }
}

class Modyllic_Index_Foreign extends Modyllic_Index {
    public $cname = "";
    const WEAK_DEFAULT = false;
    public $weak     = self::WEAK_DEFAULT;
    public $references = array();
    /**
     * @param string $name
     */
    function __construct($name="") {
        parent::__construct($name);
        $this->references['table'] = "";
        $this->references['columns'] = array();
        $this->references['on_delete'] = "";
        $this->references['on_update'] = "";
    }

    function getName() {
        return "~".$this->cname;
    }

    function equalTo($other) {
        if ( ! parent::equalTo($other) )               { return false; }
        if ( $this->references != $other->references ) { return false; }
        if ( $this->weak != $other->weak )             { return false; }
        return true;
    }
}

class Modyllic_CodeBody extends Modyllic_Diffable {
    public $body = "BEGIN\nEND";
    /**
     * @returns string Strips any comments from the body of the routine--
     * this allows the body to be compared to the one in the database,
     * which never has comments.
     */
    function _body_no_comments() {
        $stripped = $this->body;
        # Strip C style comments
        $stripped = preg_replace('{/[*].*?[*]/}s', '', $stripped);
        # Strip shell and SQL style comments
        $stripped = preg_replace('/(#|--).*/', '', $stripped);
        # Strip leading and trailing whitespace
        $stripped = preg_replace('/^[ \t]+|[ \t]+$/m', '', $stripped);
        # Collapse repeated newlines
        $stripped = preg_replace('/\n+/', "\n", $stripped);
        return $stripped;
    }

    function equalTo($other) {
        if ( get_class($other) != get_class($this) )   { return false; }
        if ( $this->_body_no_comments() != $other->_body_no_comments() ) { return false; }
        return true;
    }

}

/**
 * A collection of attributes describing an event
 */
class Modyllic_Event extends Modyllic_CodeBody {
    public $name;
    public $schedule;
    public $preserve = false;
    public $status;
    public $docs = "";

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }

    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->schedule != $other->schedule ) { return false; }
        if ( $this->preserve != $other->preserve ) { return false; }
        if ( $this->status != $other->status ) { return false; }
        return true;
    }
}

/**
 * A collection of attributes describing an event
 */
class Modyllic_Trigger extends Modyllic_CodeBody {
    public $name;
    public $time;
    public $event;
    public $table;
    public $body;
    public $docs = "";

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }

    function equalTo($other) {
        if ( ! parent::equalTo($other)     ) { return false; }
        if ( $this->time != $other->time   ) { return false; }
        if ( $this->event != $other->event ) { return false; }
        if ( $this->body != $other->body   ) { return false; }
        return true;
    }
}

/**
 * A collection of attributes describing a stored routine
 */
class Modyllic_Routine extends Modyllic_CodeBody {
    public $name;
    public $args = array();
    const ARGS_TYPE_DEFAULT = "LIST";
    public $args_type = self::ARGS_TYPE_DEFAULT;
    const DETERMINISTIC_DEFAULT = false;
    public $deterministic = self::DETERMINISTIC_DEFAULT;
    const ACCESS_DEFAULT = "CONTAINS SQL";
    public $access = self::ACCESS_DEFAULT;
    public $returns;
    const TXNS_NONE = 0;
    const TXNS_CALL = 1;
    const TXNS_HAS  = 2;
    const TXNS_DEFAULT = self::TXNS_NONE;
    public $txns = self::TXNS_DEFAULT;
    public $docs = '';

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }

    /**
     * @param Modyllic_Routine $other
     * @returns bool True if $other is equivalent to $this
     */
    function equalTo($other) {
        if ( ! parent::equalTo($other) ) { return false; }
        if ( $this->deterministic != $other->deterministic ) { return false; }
        if ( $this->access        != $other->access )        { return false; }
        if ( $this->args_type     != $other->args_type )     { return false; }
        if ( $this->txns          != $other->txns )          { return false; }
        $thisargc = count($this->args);
        $otherargc = count($other->args);
        if ( $thisargc != $otherargc ) { return false; }
        for ( $ii=0; $ii<$thisargc; ++$ii ) {
            if ( ! $this->args[$ii]->equalTo( $other->args[$ii] ) ) { return false; }
        }
        return true;
    }
}

/**
 * A stored procedure, which is exactly like the base routine class
 */
class Modyllic_Proc extends Modyllic_Routine {
    const RETURNS_TYPE_DEFAULT = "NONE";
    public $returns = array("type"=>self::RETURNS_TYPE_DEFAULT);
    function equalTo($other) {
        if ( ! parent::equalTo( $other ) ) { return false; }
        if ( $this->returns != $other->returns ) { return false; }
        return true;
    }
}

/**
 * A collection of attributes describing a stored function
 */
class Modyllic_Func extends Modyllic_Routine {
    function equalTo($other) {
        if ( ! parent::equalTo( $other ) ) { return false; }
        if ( ! $this->returns->equalTo( $other->returns ) ) { return false; }
        return true;
    }
}

/**
 * A collection of attributes describing an argument to a stored procedure
 * or function.
 */
class Modyllic_Arg extends Modyllic_Diffable {
    public $name;
    public $type;
    public $dir = "IN";
    public $docs = "";
    function toSql() {
        $sql = "";
        if ( $dir != "IN" ) {
            $sql .= "$dir ";
        }
        $sql .= Modyllic_SQL::quote_ident($name)." ";
        $sql .= $type->toSql();
        return $sql;
    }
    function equalTo($other) {
        if ( $this->name != $other->name ) { return false; }
        if ( $this->dir != $other->dir ) { return false; }
        if ( ! $this->type->equalTo($other->type) ) { return false; }
        return true;
    }
}


