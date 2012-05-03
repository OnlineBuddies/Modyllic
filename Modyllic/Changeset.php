<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Changeset/Schema.php";
require_once "Modyllic/Changeset/Event.php";

/**
 * This is the actual differences between two schema
 */
class Modyllic_Changeset {
    public $add;
    public $remove;
    public $update;
    public $schema;

    function __construct() {
        $this->add = array(
            "tables" => array(),
            "routines"  => array(),
            "events" => array(),
            "views"  => array(),
            "triggers" => array(),
            );
        $this->remove = array(
            "tables" => array(),
            "routines"  => array(),
            "events" => array(),
            "views"  => array(),
            "triggers" => array(),
            );
        $this->update = array(
            "tables" => array(),
            "routines"  => array(),
            "events" => array(),
            "views"  => array(),
            "triggers" => array(),
            );
        $this->schema = new Modyllic_Changeset_Schema();
    }

    /**
     * Note that a table was added
     * @param Modyllic_Schema_Table $table
     */
    function add_table( Modyllic_Schema_Table $table ) {
        $this->add['tables'][$table->name] = $table;
    }

    /**
     * Note that a table was removed
     * @param Modyllic_Schema_Table $table
     */
    function remove_table( Modyllic_Schema_Table $table ) {
        $this->remove['tables'][$table->name] = $table;
    }

    /**
     * Note that a table was updated (and how)
     * @param Modyllic_Table_Changeset $table
     */
    function update_table( Modyllic_Table_Changeset $table ) {
        $this->update['tables'][$table->name] = $table;
    }

    /**
     * @param Modyllic_Schema_View $view
     */
    function add_view( Modyllic_Schema_View $view ) {
        $this->add['views'][$view->name] = $view;
    }

    /**
     * @param Modyllic_View_Changeset $view
     */
    function update_view( Modyllic_View_Changeset $view ) {
        $this->update['views'][$view->name] = $view;
    }

    /**
     * @param Modyllic_Schema_View $view
     */
    function remove_view( Modyllic_Schema_View $view ) {
        $this->remove['views'][$view->name] = $view;
    }

    /**
     * Note that a routine was added
     * @param Modyllic_Schema_Routine $routine
     */
    function add_routine( Modyllic_Schema_Routine $routine ) {
        $this->add['routines'][$routine->name] = $routine;
    }

    /**
     * Note that a routine was removed
     * @param Modyllic_Schema_Routine $routine
     */
    function remove_routine( Modyllic_Schema_Routine $routine ) {
        $this->remove['routines'][$routine->name] = $routine;
    }

    /**
     * Note that a routine was updated
     * @param Modyllic_Schema_Routine $routine
     */
    function update_routine( Modyllic_Schema_Routine $routine ) {
        $this->update['routines'][$routine->name] = $routine;
    }

    /**
     * @param Modyllic_Schema_Event $event
     */
    function add_event( Modyllic_Schema_Event $event ) {
        $this->add['events'][$event->name] = $event;
    }

    /**
     * @param Modyllic_Changeset_Event $event
     */
    function update_event( Modyllic_Changeset_Event $event ) {
        $this->update['events'][$event->name] = $event;
    }

    /**
     * @param Modyllic_Schema_Event $event
     */
    function remove_event( Modyllic_Schema_Event $event ) {
        $this->remove['events'][$event->name] = $event;
    }

    /**
     * @param Modyllic_Schema_Trigger $trigger
     */
    function add_trigger( Modyllic_Schema_Trigger $trigger ) {
        $this->add['triggers'][$trigger->name] = $trigger;
    }

    /**
     * @param Modyllic_Trigger_Changeset $trigger
     */
    function update_trigger( $trigger ) {
        $this->update['triggers'][$trigger->name] = $trigger;
    }

    /**
     * @param Modyllic_Schema_Trigger $trigger
     */
    function remove_trigger( Modyllic_Schema_Trigger $trigger ) {
        $this->remove['triggers'][$trigger->name] = $trigger;
    }

    /**
     * Check to see if this object actually contains any changes
     */
    function has_changes() {
        $changed = (count($this->add['tables'  ]) + count($this->update['tables'  ]) + count($this->remove['tables'  ]) +
                    count($this->add['routines']) + count($this->update['routines']) + count($this->remove['routines']) +
                    count($this->add['events'  ]) + count($this->update['events'  ]) + count($this->remove['events'  ]) +
                    count($this->add['views'   ]) + count($this->update['views'   ]) + count($this->remove['views'   ]) +
                    count($this->add['triggers']) + count($this->update['triggers']) + count($this->remove['triggers'])
                    );
        return ($changed != 0 or isset($this->schema->charset) or isset($this->schema->collate));
    }
}
