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
    public $to;
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
    function update_partition($option,$fromvalue,$tovalue) {

        $this->options->$option->remove = $fromvalue;
        $this->options->$option->add = $tovalue;
        $this->options->action="update";

    }
    function add_partition($option,$value) {

         $this->options->$option = $value;
         $this->options->action="add";

    }
    function remove_partition($option,$value) {

        $this->options->$option = $value;
        $this->options->action="remove";

    }

    /**
     * @param array $row
     */
    function add_row(Modyllic_Schema_Table_Row $row,array $pk) {
        $this->add['data'][] = array("data"=>$row,"where"=>$pk);
    }

    /**
     * @param Modyllic_Schema_Table_Row $row
     */
    function remove_row(Modyllic_Schema_Table_Row $row) {
        $this->remove['data'][] = array("where"=>$row);
    }

    /**
     * @param array $updated
     * @param array $where
     * @param Modyllic_Schema_Table_Row $from
     */
    function update_row(array $updated,array $where,Modyllic_Schema_Table_Row $from) {
        $this->update['data'][] = array("data"=>$updated,"where"=>$where,"from"=>$from);
    }

    function has_data_changes() {
         return count($this->add['data']) + count($this->remove['data']) + count($this->update['data']);
    }

    /**
     * Check to see if this object actually contains any changes
     */
    function has_changes() {
         return $this->has_schema_changes() or $this->has_data_changes();
    }

    function has_schema_changes() {
        $changed
            = count($this->add['columns']) + count($this->remove['columns']) + count($this->update['columns'])
            + count($this->add['indexes']) + count($this->remove['indexes']) + isset($this->static)
            + $this->options->has_changes()
            ;
         return $changed;
    }

    function match_row($row) {
        return $this->to->match_row($row);
    }

}
