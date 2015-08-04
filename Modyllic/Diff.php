<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * This figures out how one schema differs from another
 */
class Modyllic_Diff {
    public $from;
    public $to;
    public $changeset;

    /**
     * @param Modyllic_Schema $from
     * @param Modyllic_Schema $to
     */
    function __construct(Modyllic_Schema $from, Modyllic_Schema $to) {
        $this->from = $from;
        $this->to   = $to;
        $this->calculate_changeset();
    }

    /**
     * @returns Modyllic_Changeset
     */
    function changeset() {
        return $this->changeset;
    }

    private function calculate_changeset() {
        $this->changeset = new Modyllic_Changeset();

        $this->changeset->schema->name = $this->to->name;
        $this->changeset->schema->from = $this->from;
        $this->changeset->schema->to   = $this->to;
        if ( $this->from->charset != $this->to->charset ) {

            $this->changeset->schema->charset = $this->to->charset;
        }
        if ( $this->from->collate != $this->to->collate ) {
            $this->changeset->schema->collate = $this->to->collate;
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
            $from=array();
            $to=array();
            if ( ! isset($this->from->routines[$name]) ) {
                $this->changeset->add_routine($routine);
            }elseif($routine instanceOf Modyllic_Schema_Func){
            if($to=$routine->args != $from=$this->from->routines[$name]->args){//args comparison

                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);


                }elseif($to=$routine->args_type != $from=$this->from->routines[$name]->args_type){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }
                elseif($to=$routine->deterministic != $from=$this->from->routines[$name]->deterministic){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }elseif($to=$routine->access != $from=$this->from->routines[$name]->access){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }elseif($to=$routine->txns != $from=$this->from->routines[$name]->txns){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }elseif($to=$routine->docs != $from=$this->from->routines[$name]->docs){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }elseif($to=$routine->body != $from=$this->from->routines[$name]->body){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }elseif($to=$routine->from != $from=$this->from->routines[$name]->from){
                    $other = $this->from->routines[$name];
                    $routine->from = $this->from->routines[$name];
                    $this->changeset->update_routine($routine);
                }
            }
            elseif($routine instanceOf Modyllic_Schema_Proc){
                if ( ! $routine->equal_to($this->from->routines[$name]) ) {
                $other = $this->from->routines[$name];
                $routine->from = $this->from->routines[$name];
                $this->changeset->update_routine($routine);
                }
            }

            /*else if ( ! $routine->equal_to($this->from->routines[$name]) ) {
                $other = $this->from->routines[$name];
                $routine->from = $this->from->routines[$name];
                echo'<pre>';
                print_r($this->from->routines[$name]);
                $this->changeset->update_routine($routine);
            }*/
        }

        # New and updated events
        foreach ($this->to->events as $name=>$toevt) {
            if ( ! isset($this->from->events[$name]) ) {
                $this->changeset->add_event($toevt);
                continue;
            }
            $fromevt = $this->from->events[$name];
            if ( ! $toevt->equal_to($fromevt) ) {
                $updevt = new Modyllic_Changeset_Event();
                $updevt->name = $name;
                $updevt->from = $fromevt;
                if ( ! $toevt->schedule->equal_to($fromevt->schedule) ) {
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
            if ( ! $toview->equal_to($fromview) ) {
                $toview->from = $fromview;
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

        # New and updated triggers
        foreach ($this->to->triggers as $name=>$totrigger) {
            if ( ! isset($this->from->triggers[$name]) ) {
                $this->changeset->add_trigger($totrigger);
                continue;
            }
            $fromtrigger = $this->from->triggers[$name];
            if ( ! $totrigger->equal_to($fromtrigger) ) {
                $totrigger->from = $fromtrigger;
                $this->changeset->update_trigger( $totrigger );
            }
        }
        # Deleted triggers
        foreach ($this->from->triggers as $name=>$fromtrigger) {
            if ( ! isset($this->to->triggers[$name]) ) {
                if ( isset($this->to->tables[$name]) ) {
                    # If a table exists for this trigger, then we'll ignore this
                }
                else {
                    $this->changeset->remove_trigger($fromtrigger);
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

            $tablediff = new Modyllic_Changeset_Table($tablename);

            $tablediff->from = $fromtable;
            $tablediff->to   = $totable;



            if ( $fromtable->static != $totable->static ) {
                echo'-------------static-----------------';
                $tablediff->static = $totable->static;
            }

            # Check to see if any options changed
            if ( $totable->engine != $fromtable->engine ) {
                $tablediff->update_option( 'engine', $totable->engine );
            }
            if ( $totable->row_format != $fromtable->row_format ) {
                $tablediff->update_option( 'row_format', $totable->row_format );
            }
            if ( $totable->charset != $fromtable->charset ) {

                $tablediff->update_option( 'charset', $totable->charset );
            }
            if ( $totable->collate != $fromtable->collate ) {

                $tablediff->update_option( 'collate', $totable->collate );
            }
            if(isset($fromtable->partition)&&isset($totable->partition)){

            if ( $totable->partition != $fromtable->partition ) {
                $tablediff->update_partition( 'partition', $fromtable->partition,$totable->partition );
            }}
            elseif(isset($fromtable->partition)&& !isset($totable->partition)){

               $tablediff->remove_partition( 'partition', $fromtable->partition );

            }elseif(!isset($fromtable->partition)&&isset($totable->partition)){

                $tablediff->add_partition( 'partition', $totable->partition );
            }


            /*partition changes rajesh
            if ( $totable->partition != $fromtable->partition ) {
                $tablediff->update_option( 'partition', $totable->partition );
            }
                till here
             echo'<pre>'.'diff table from and to ';print_r($fromtable);echo'<br>';print_r($totable);echo'</pre>';
            */

            # First let's build some column maps:
            $tonames = array();
            $fromnames = array();
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
         //   echo'<pre>'.'table diffences'.'table differences';print_r($tablediff->options);echo'</pre>';
            # Find new and updated columns
         //   echo'<pre>'.'totable columns';print_r($totable->columns);echo'</pre>';
            foreach ( $totable->columns as $name=>$tocolumn ) {
                if ( isset( $tonames[$name] ) ) {
                    $fromname = $tonames[$name];
                }
                else {
                    $tablediff->add_column($tocolumn);
                    continue;
                }
                $fromcolumn = $fromtable->columns[$fromname];
                $tocolumn->from = $fromcolumn;
                if ( ! $tocolumn->equal_to($fromcolumn) ) {
                    $tocolumn->previously = $fromname;
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
                $match = false;
                foreach ($fromtable->indexes as $name=>$fromindex ) {
                    $match = $toindex->equal_to( $fromindex, $fromnames );
                    if ( $match ) { break; }
                }
                if ( ! $match ) {
                    $tablediff->add_index( $toindex );
                }
            }
            ##### Next, detect removed indexes
            foreach ( $fromtable->indexes as $name=>$fromindex ) {
                $match = false;
                foreach ($totable->indexes as $name=>$toindex ) {
                    $match = $fromindex->equal_to( $toindex, $tonames );
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
                $tablediff->update_option("static",true);
            }
            else if ( ! isset($totable->data) and isset($fromtable->data) ) {
                $tablediff->update_option("static",false);
            }

            if (serialize($totable->primary_key()) != serialize($fromtable->primary_key())) {
                $indexed = array();
                foreach ($from_data as $row) {
                    $indexed[$totable->get_row_primary_key($row)] = $row;
                }
                $from_data = $indexed;
            }
            if ( isset($totable->data) ) {
                # First, new and updated rows
                foreach ( $to_data as $topk=>$torow ) {
                    if (isset($from_data[$topk])) {
                        $fromrow = $from_data[$topk];
                        $set = array();
                        foreach ($torow as $col=>$toval) {
                            if ( !isset($fromrow[$col]) or ! $toval->equal_to($fromrow[$col],$totable->columns[$col]->type) ) {
                                $set[$col] = $toval;
                            }
                        }
                        if ( count($set) ) {
                            $tablediff->update_row($set,$totable->match_row($fromrow),$fromrow);
                        }
                    }
                    else {
                        $tablediff->add_row($torow,$totable->match_row($torow));
                    }
                }
                # And then removed rows
                foreach ( $from_data as $frompk=>$fromrow ) {
                    $exists = false;
                    if (! isset($to_data[$frompk])) {
                        $tablediff->remove_row(new Modyllic_Schema_Table_Row($fromtable->match_row($fromrow)));
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
