<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * This generates MySQL SQL such that each ALTER statement is issued
 * separately.  This is necessary for certain replication scenarios, eg,
 * 5.5 master, 5.6 slave.
 */
class Modyllic_Generator_SeparateAlterMySQL extends Modyllic_Generator_MySQL {
    function alter_table( $table ) {
        if ( ! isset($this->what['meta']) and $table instanceOf Modyllic_Schema_MetaTable ) { return; }
        if ( $table->has_schema_changes() ) {
            if ( $table->options->has_changes() or
                 count($table->add['columns'])+count($table->remove['columns'])+count($table->update['columns'])+
                 count($table->add['indexes'])+count($table->remove['indexes']) > 0 ) {
                if ($table->options->has_changes()) {
                    $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                    $this->table_options( $table->options );
                    $this->end_cmd();
                }
                foreach ($table->remove['indexes'] as $index) {
                    if ( $index instanceOf Modyllic_Schema_Index_Foreign ) {
                        $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                        $this->drop_index($index);
                        $this->end_cmd();
                    }
                }
                foreach ($table->remove['indexes'] as $index) {
                    if ( ! $index instanceOf Modyllic_Schema_Index_Foreign ) {
                        $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                        $this->drop_index($index);
                        $this->end_cmd();
                    }
                }
                foreach (array_reverse($table->add['columns']) as $column) {
                    $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                    $this->add_column( $column );
                    $this->end_cmd();
                }
                foreach ($table->remove['columns'] as $column) {
                    $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                    $this->drop_column($column);
                    $this->end_cmd();
                }
                foreach ($table->update['columns'] as $column) {
                    $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                    $this->alter_column($column);
                    $this->end_cmd();
                }
                $constraints = array();
                foreach ($table->add['indexes'] as $index) {
                    if ( $index instanceOf Modyllic_Schema_Index_Foreign ) {
                        $constraints[] = $index;
                    }
                    else {
                        $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                        $this->add_index($index);
                        $this->end_cmd();
                    }
                }
                if (count($constraints)) {
                    foreach ($constraints as $index) {
                        $this->begin_cmd( "ALTER TABLE %id ", $table->name );
                        $this->add_index($index);
                        $this->end_cmd();
                    }
                }
            }
        }
        $this->alter_table_data($table);
        return $this;
    }
}
