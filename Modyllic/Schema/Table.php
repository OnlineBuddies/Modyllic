<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of columns, indexes and other information comprising a table
 */
class Modyllic_Schema_Table extends Modyllic_Diffable {
    public $name;
    public $columns = array();
    public $indexes = array();
    const STATIC_DEFAULT = false;
    public $static = self::STATIC_DEFAULT;
    public $data = array();
    public $last_column;
    public $last_index;
    public $engine = 'InnoDB';
    const ROW_FORMAT_DEFAULT = 'DEFAULT';
    public $row_format = self::ROW_FORMAT_DEFAULT;
    public $charset = 'utf8';
    public $collate = 'utf8_general_ci';
    public $docs = "";
    public $errors = array();
    /**
     * @param string $name
     */
    function __construct($name=null) {
        if (isset($name)) {
            $this->name = $name;
        }
    }

    function copy_from($table) {
        $this->name = $table->name;
        $this->columns = unserialize(serialize($table->columns));
        $this->indexes = unserialize(serialize($table->indexes));
        $this->static = $table->static;
        $this->data = unserialize(serialize($table->data));
        $this->last_column = unserialize(serialize($table->last_column));
        $this->last_index = unserialize(serialize($table->last_index));
        $this->engine = $table->engine;
        $this->row_format = $table->row_format;
        $this->charset = $table->charset;
        $this->collate = $table->collate;
        $this->docs = $table->docs;
    }

    /**
     * @param Modyllic_Schema_Column $column
     */
    function add_column(Modyllic_Schema_Column $column) {
        if ( isset($this->last_column) ) {
            $column->after = $this->last_column;
        }
        $this->last_column = $column;
        $this->columns[$column->name] = $column;
        return $column;
    }

    /**
     * @param Modyllic_Schema_Index $index
     */
    function add_index(Modyllic_Schema_Index $index) {
        $name = $index->get_name();
        if ( isset($this->indexes[$name]) ) {
            throw new Exception("In table ".$this->name."- duplicate key name ".$name);
        }
        $this->indexes[$name] = $index;
        $this->last_index = $index;
        foreach ($index->columns as $cname=>$value) {
            if ( isset($this->columns[$cname]) ) {
                if ( $index->primary ) {
                    $this->columns[$cname]->null = false;
                    if ( $this->columns[$cname]->default == 'NULL' ) {
                        $this->columns[$cname]->default = null;
                    }
                }
            }
            else {
                throw new Exception("In table ".$this->name.", index $name, can't index unknown column $cname");
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
     * @param Modyllic_Schema_Table $other
     * @returns bool True if $other is equivalent to $this
     */
    function equal_to( Modyllic_Schema_Table $other ) {
        if ( $this->name != $other->name ) { return false; }
        if ( $this->engine != $other->engine ) { return false; }
        if ( $this->row_format != $other->row_format ) { return false; }
        if ( $this->charset != $other->charset ) { return false; }
        if ( $this->static != $other->static ) { return false; }
        if ( count($this->columns) != count($other->columns) ) { return false; }
        if ( count($this->indexes) != count($other->indexes) ) { return false; }
        foreach ( $this->columns as $key=>&$value ) {
            if ( ! $value->equal_to( $other->columns[$key] ) ) { return false; }
        }
        foreach ( $this->indexes as $key=>&$value ) {
            if ( ! $value->equal_to( $other->indexes[$key] ) ) { return false; }
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

    function upgrade_row( array $row ) {
    }

    /**
     * Add a row of data to this table
     * @throws Exception when data is not yet initialized.
     */
    function add_row( array $row ) {
        if ( ! $this->static and ! $this instanceOf Modyllic_Schema_MetaTable ) {
            $this->errors[] = "Adding data to a non-static table is meaningless-- call TRUNCATE first";
        }
        foreach ($row as $col_name=>&$value) {
            if ( ! isset($this->columns[$col_name]) ) {
                $this->errors[] = "INSERT references $col_name in ".$this->name." but $col_name doesn't exist";
                continue;
            }
            $row[$col_name] = Modyllic_Expression::create($value);
        }
        if (! count($row)) return;
        $element = null;
        $pk = $this->primary_key();
        foreach ($this->data as $index=>$cur) {
            $match = true;
            foreach ( $pk as $col=>$col_obj ) {
                $type = $this->columns[$col]->type;
                if ( ! isset($cur[$col]) or ! $cur[$col]->equal_to($row[$col],$type) ) {
                    $match = false;
                    break;
                }
            }
            if ( $match ) {
                $element = $index;
                break;
            }
        }
        $row = new Modyllic_Schema_Table_Row($row);
        if ( isset($element) ) {
            $this->data[$element] = $row;
        }
        else {
            $this->data[] = $row;
        }
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
    function match_row(Modyllic_Schema_Table_Row $row) {
        $where = array();
        foreach ($this->primary_key() as $key=>$len) {
             $where[$key] = @$row[$key];
        }
        return $where;
    }

    function validate($schema) {
        $errors = $this->errors;
        if (preg_match('/\0/',$this->name)) {
            $errors[] = 'Table names may not contain NUL characters';
        }
        if (preg_match('/\s$/u', $this->name)) {
            $errors[] = 'Table names may not end in whitespace';
        }
        /// @todo engine
        /// @todo row_format
        /// @todo charset
        /// @todo collate
        foreach ($this->columns as $column) {
            $errors = array_merge($errors, $column->validate($schema,$this));
        }
        foreach ($this->indexes as $index) {
            $errors = array_merge($errors, $index->validate($schema,$this));
        }
        return $errors;
    }
}
