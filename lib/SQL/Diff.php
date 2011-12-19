<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/Generator.php";

/**
 * This figures out how one schema differs from another
 */
class SQL_Diff {
    public $from;
    public $to;
    public $changeset;

    /**
     * @param SQL_Schema $from
     * @param SQL_Schema $to
     */
    function __construct($from, $to) {
        $this->from = $from;
        $this->to   = $to;
        $this->calculate_changeset();
    }
    
    /**
     * @returns SQL_Changeset
     */
    function changeset() {
        return $this->changeset;
    }
    
    private function calculate_changeset() {
        $this->changeset = new SQL_Changeset();
        
        $this->changeset->schema->name = $this->to->name;
        $this->changeset->schema->from = $this->from;
        if ( $this->from->charset != $this->to->charset ) {
            $this->changeset->schema->charset = $this->to->charset;
        }
        if ( $this->from->collate != $this->to->collate ) {
            $this->changeset->schema->collate = $this->to->collate;
        }

        // If our metadata table doesn't yet exist, create it
        if ( ! $this->from->sqlmeta_exists ) {
            $this->changeset->create_sqlmeta = TRUE;
            
        }
        
        # Find completely new tables
        foreach ($this->to->tables as $name=>$table) {
            if ( ! isset($this->from->tables[$name]) ) {
                if ( isset($this->from->views[$name]) ) {
                    # If this table currently exists as a view, ignore it
                }
                else {
                    $this->changeset->add_table($table);
                }
            }
        }
        
        # Find new and updated routines
        foreach ($this->to->routines as $name=>$routine) {
            if ( ! isset($this->from->routines[$name]) ) {
                $this->changeset->add_routine($routine);
            }
            else if ( ! $routine->equalTo($this->from->routines[$name]) ) {
                $other = $this->from->routines[$name];
                $routine->from = $this->from->routines[$name];
                $this->changeset->update_routine($routine);
            }
        }

        # New and updated events
        foreach ($this->to->events as $name=>$toevt) {
            if ( ! isset($this->from->events[$name]) ) {
                $this->changeset->add_event($toevt);
                continue;
            }
            $fromevt = $this->from->events[$name];
            if ( ! $toevt->equalTo($fromevt) ) {
                $updevt = new SQL_Event_Changeset();
                $updevt->name = $name;
                $updevt->from = $fromevt;
                if ( $toevt->schedule != $fromevt->schedule ) {
                    $updevt->schedule = $toevt->schedule;
                }
                if ( $toevt->preserve != $fromevt->preserve ) {
                    $updevt->preserve = $toevt->preserve;
                }
                if ( $toevt->status != $fromevt->status ) {
                    $updevt->status = $toevt->status;
                }
                if ( $toevt->_body_no_comments() != $fromevt->_body_no_comments() ) {
                    $updevt->body = $toevt->body;
                }
                $this->changeset->update_event( $updevt );
            }
        }
        # Deleted events
        foreach ($this->from->events as $name=>$fromevt) {
            if ( ! isset($this->to->events[$name]) ) {
                $this->changeset->remove_event($fromevt);
            }
        }

        # New and updated views
        foreach ($this->to->views as $name=>$toview) {
            if ( ! isset($this->from->views[$name]) ) {
                $this->changeset->add_view($toview);
                continue;
            }
            $fromview = $this->from->views[$name];
            if ( ! $toview->equalTo($fromview) ) {
                $this->changeset->update_view( $toview );
            }
        }
        # Deleted views
        foreach ($this->from->views as $name=>$fromview) {
            if ( ! isset($this->to->views[$name]) ) {
                if ( isset($this->to->tables[$name]) ) {
                    # If a table exists for this view, then we'll ignore this
                }
                else {
                    $this->changeset->remove_view($fromview);
                }
            }
        }

        # Find removed tables
        foreach ($this->from->tables as $name=>$table) {
            if ( ! isset($this->to->tables[$name]) ) {
                $this->changeset->remove_table($table);
            }
        }
        
        # Find removed routines
        foreach ($this->from->routines as $name=>$routine) {
            if ( ! isset($this->to->routines[$name]) ) {
                $this->changeset->remove_routine($routine);
            }
        }

        # For each table, find table changes
        foreach ($this->to->tables as $tablename=>$totable) {
            if ( ! isset($this->from->tables[$tablename]) ) { continue; }
            $fromtable = $this->from->tables[$tablename];
            
            $tablediff = new SQL_Table_Changeset($tablename);
            
            $tablediff->from = $fromtable;
            
            if ( $fromtable->static != $totable->static ) {
                $tablediff->static = $totable->static;
            }
            
            # Check to see if any options changed
            if ( $totable->engine != $fromtable->engine ) {
                $tablediff->update_option( 'engine', $totable->engine );
            }
            if ( $totable->charset != $fromtable->charset ) {
                $tablediff->update_option( 'charset', $totable->charset );
            }
            if ( $totable->collate != $fromtable->collate ) {
                $tablediff->update_option( 'collate', $totable->collate );
            }
            
            # First let's build some column maps:
            $tonames = array(); $fromnames = array();
            foreach ( $totable->columns as $toname=>$tocolumn ) {
                # If the totable name is in the fromtable...
                if ( isset($fromtable->columns[$toname]) ) {
                    $tonames[$toname] = $toname;
                    $fromnames[$toname] = $toname;
                    continue;
                }
                # If a totable alias is in the fromtable...
                foreach ( $tocolumn->aliases as $alias ) {
                    if ( isset($fromtable->columns[$alias]) ) {
                        $tonames[$toname] = $alias;
                        $fromnames[$alias] = $toname;
                        break;
                    }
                }
            }
            foreach ( $fromtable->columns as $fromname=>$fromcolumn ) {
                // If we already have a mapping, skip
                if ( isset($fromnames[$fromname]) ) { continue; }
                # If a fromtable alias is in the totable...
                foreach ( $fromcolumn->aliases as $alias ) {
                    if ( isset($totable->columns[$alias]) ) {
                        $tonames[$alias] = $fromname;
                        $fromnames[$fromname] = $alias;
                        break;
                    }
                }
            }
            
            # Find new and updated columns
            foreach ( $totable->columns as $name=>$tocolumn ) {
                if ( isset( $tonames[$name] ) ) {
                    $fromname = $tonames[$name];
                }
                else {
                    $tablediff->add_column($tocolumn);
                    continue;
                }
                $fromcolumn = $fromtable->columns[$fromname];
                if ( ! $tocolumn->equalTo($fromcolumn) ) {
                    $tocolumn->previously = $fromname;
                    $tocolumn->from = $fromcolumn;
                    $tablediff->update_column($tocolumn);
                }
                
            }
            
            # Find removed columns;
            foreach ( $fromtable->columns as $name=>$fromcolumn ) {
                if ( ! isset($fromnames[$name]) ) {
                    $tablediff->remove_column($fromcolumn);
                }
            }
            
            ##### First, detect new indexes
            foreach ( $totable->indexes as $name=>$toindex ) {
                $match = FALSE;
                foreach ($fromtable->indexes as $name=>$fromindex ) {
                    $match = $toindex->equalTo( $fromindex );
                    if ( $match ) { break; }
                }
                if ( ! $match ) {
                    $tablediff->add_index( $toindex );
                }
            }
            ##### Next, detect removed indexes
            foreach ( $fromtable->indexes as $name=>$fromindex ) {
                $match = FALSE;
                foreach ($totable->indexes as $name=>$toindex ) {
                    $match = $fromindex->equalTo( $toindex );
                    if ( $match ) { break; }
                }
                if ( ! $match ) {
                    $tablediff->remove_index( $fromindex );
                }
            }
            
            # Find data changes...
            $from_data = isset($fromtable->data)? $fromtable->data: array();
            $to_data   = isset($totable->data)?   $totable->data:   array();

            if ( isset($totable->data) and ! isset($fromtable->data) ) {
                $tablediff->update_option("static",TRUE);
            }
            else if ( ! isset($totable->data) and isset($fromtable->data) ) {
                $tablediff->update_option("static",FALSE);
            }
            if ( isset($totable->data) ) {
                $primary = $totable->primary_key();
                
                # First, new and updated rows
                foreach ( $to_data as $torow ) {
                    $exists = FALSE;
                    foreach ( $from_data as $fromrow ) {
                        $match = TRUE;
                        foreach ( $primary as $key ) {
                            if ( @$torow[$key] != @$fromrow[$key] ) {
                                $match = FALSE;
                                break;
                            }
                        }
                        if ( $match ) {
                            $exists = TRUE;
                            $set = array();
                            foreach ($torow as $col=>$toval) {
                                if ( !isset($fromrow[$col]) or $fromrow[$col]!=$toval ) {
                                    $set[$col] = $toval;
                                }
                            }
                            if ( count($set) ) {
                                $tablediff->update_row($set,$totable->match_row($fromrow),$fromrow);
                            }
                            break;
                        }
                    }
                    if (!$exists) {
                        $tablediff->add_row($torow);
                    }
                }
                # And then removed rows
                foreach ( $from_data as $fromrow ) {
                    $exists = FALSE;
                    foreach ( $to_data as $torow ) {
                        $match = TRUE;
                        foreach ( $primary as $key ) {
                            if ( @$torow[$key] != @$fromrow[$key] ) {
                                $match = FALSE;
                                break;
                            }
                        }
                        if ( $match ) {
                            $exists = TRUE;
                            break;
                        }
                    }
                    if (!$exists) {
                        $tablediff->remove_row($fromtable->match_row($fromrow));
                    }
                }
            }
            
            # If anything in this table changed, then we mark the table as updated.
            if ( $tablediff->has_changes() ) {
                $tablediff->from = $fromtable;
                $this->changeset->update_table( $tablediff );
            }
        }
        
    }
}

/**
 * This is the actual differences between two schema
 */
class SQL_Changeset {
    public $add;
    public $remove;
    public $update;
    public $schema;
    public $create_sqlmeta = FALSE;
    
    function __construct() {
        $this->add = array(
            "tables" => array(),
            "routines"  => array(),
            "events" => array(),
            "views"  => array(),
            );
        $this->remove = array(
            "tables" => array(),
            "routines"  => array(),
            "events" => array(),
            "views"  => array(),
            );
        $this->update = array(
            "tables" => array(),
            "routines"  => array(),
            "events" => array(),
            "views"  => array(),
            );
        $this->schema = new SQL_Schema_Changeset();
    }
    
    /**
     * Note that a table was added
     * @param SQL_Table $table
     */
    function add_table( $table ) {
        $this->add['tables'][$table->name] = $table;
    }
    
    /**
     * Note that a table was removed
     * @param SQL_Table $table
     */
    function remove_table( $table ) {
        $this->remove['tables'][$table->name] = $table;
    }
    
    /**
     * Note that a table was updated (and how)
     * @param SQL_Table_Changeset $table
     */
    function update_table( $table ) {
        $this->update['tables'][$table->name] = $table;
    }
    
    /**
     * Note that a routine was added
     * @param SQL_Routine $routine
     */
    function add_routine( $routine ) {
        $this->add['routines'][$routine->name] = $routine;
    }
    
    /**
     * Note that a routine was removed
     * @param SQL_Routine $routine
     */
    function remove_routine( $routine ) {
        $this->remove['routines'][$routine->name] = $routine;
    }
    
    /**
     * Note that a routine was updated
     * @param SQL_Routine $routine
     */
    function update_routine( $routine ) {
        $this->update['routines'][$routine->name] = $routine;
    }
    
    /**
     * @param SQL_Event $event
     */
    function add_event( $event ) {
        $this->add['events'][$event->name] = $event;
    }
    
    /**
     * @param SQL_Event_Changeset $event
     */
    function update_event( $event ) {
        $this->update['events'][$event->name] = $event;
    }
    
    /**
     * @param SQL_Event $event
     */
    function remove_event( $event ) {
        $this->remove['events'][$event->name] = $event;
    }
    
    /**
     * Check to see if this object actually contains any changes
     */
    function has_changes() {
        $changed = (count($this->add['tables'])    + count($this->add['routines'])+
                    count($this->add['events'])    + count($this->add['views'])+
                    count($this->remove['tables']) + count($this->remove['routines'])+
                    count($this->remove['events']) + count($this->remove['views'])+
                    count($this->update['tables']) + count($this->update['routines'])+
                    count($this->update['events']) + count($this->update['views'])
                    );
        return $changed != 0;
    }
}

/**
 * This stores just schema global attributes
 */
class SQL_Schema_Changeset {
    public $name;
    public $charset;
    public $collate;
    public $from;
    
    /**
     * Check to see if anything has actually been changed
     */
    function has_changes() {
        return ( isset($this->charset) or isset($this->collate) );
    }
}

/**
 * This represents how one particular table differs
 */
class SQL_Table_Changeset {
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
        $this->options = new SQL_Table_Options();
    }

    /**
     * Note that a column was added
     * @param SQL_Column $column
     */
    function add_column($column) {
        $this->add['columns'][$column->name] = $column;
    }
    
    /**
     * Note that a column was removed
     * @param SQL_Column $column
     */
    function remove_column($column) {
        $this->remove['columns'][$column->name] = $column;
    }
    
    /**
     * Note that a column was updated
     * @param SQL_Column $column
     */
    function update_column($column) {
        $this->update['columns'][$column->name] = $column;
    }
    
    /**
     * Note that an index was added
     * @param SQL_Index $index
     */
    function add_index($index) {
        $this->add['indexes'][] = $index;
    }
    
    /**
     * Note that an index was removed
     * @param SQL_Index $index
     */
    function remove_index($index) {
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
    function add_row($row) {
        $this->add['data'][] = $row;
    }
    
    /**
     * @param array $row
     */
    function remove_row($row) {
        $this->remove['data'][] = $row;
    }
    
    /**
     * @param array $updated
     * @param array $where
     */
    function update_row($updated,$where,$from) {
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

class SQL_Table_Options {
    public $charset;
    public $collate;
    public $engine;
    
    /**
     * @returns true if this object contains any changes
     */
    function has_changes() {
        return isset($this->charset) or isset($this->collate) or isset($this->engine);
    }
}

class SQL_Event_Changeset extends SQL_Diffable {
    public $name;
    public $schedule;
    public $preserve;
    public $status;
    public $body;
}
