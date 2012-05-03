<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * This represents how one particular table differs
 */
class Modyllic_Changeset_Table {
    public $name;
    public $add;
    public $remove;
    public $update;
    public $from;
    public $options;
    public $static;

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
        $this->add = array(
            "columns" => array(),
            "indexes" => array(),
            "data"    => array(),
            );
        $this->remove = array(
            "columns" => array(),
            "indexes" => array(),
            "data"    => array(),
            );
        $this->update = array(
            "columns" => array(),
            "data"    => array(),
            );
        $this->options = new Modyllic_Changeset_Table_Options();
    }

    /**
     * Note that a column was added
     * @param Modyllic_Schema_Column $column
     */
    function add_column(Modyllic_Schema_Column $column) {
        $this->add['columns'][$column->name] = $column;
    }

    /**
     * Note that a column was removed
     * @param Modyllic_Schema_Column $column
     */
    function remove_column(Modyllic_Schema_Column $column) {
        $this->remove['columns'][$column->name] = $column;
    }

    /**
     * Note that a column was updated
     * @param Modyllic_Schema_Column $column
     */
    function update_column(Modyllic_Schema_Column $column) {
        $this->update['columns'][$column->name] = $column;
    }

    /**
     * Note that an index was added
     * @param Modyllic_Schema_Index $index
     */
    function add_index(Modyllic_Schema_Index $index) {
        $this->add['indexes'][] = $index;
    }

    /**
     * Note that an index was removed
     * @param Modyllic_Schema_Index $index
     */
    function remove_index(Modyllic_Schema_Index $index) {
        $this->remove['indexes'][] = $index;
    }

    /**
     * Note that a table option was changed
     * @param string $option
     * @param string $value
     */
    function update_option($option,$value) {
        $this->options->$option = $value;
    }

    /**
     * @param array $row
     */
    function add_row(array $row) {
        $this->add['data'][] = $row;
    }

    /**
     * @param array $row
     */
    function remove_row(array $row) {
        $this->remove['data'][] = $row;
    }

    /**
     * @param array $updated
     * @param array $where
     */
    function update_row(array $updated,array $where,array $from) {
        $this->update['data'][] = array("updated"=>$updated,"where"=>$where,"from"=>$from);
    }

    /**
     * Check to see if this object actually contains any changes
     */
    function has_changes() {
         $changed_data = count($this->add['data']) + count($this->remove['data']) + count($this->update['data']);
         return $this->has_schema_changes() or $changed_data!=0;
    }

    function has_schema_changes() {
        $changed
            = count($this->add['columns']) + count($this->remove['columns']) + count($this->update['columns'])
            + count($this->add['indexes']) + count($this->remove['indexes']) + ($this->static != $this->from->static )
            + $this->options->has_changes()
            ;
         return $changed;
    }
}
