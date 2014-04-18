<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Schema_Index_Foreign extends Modyllic_Schema_Index {
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

    function get_name() {
        return "~".$this->cname;
    }

    function isConstraint() {
        return true;
    }

    function equal_to(Modyllic_Schema_Index $other, array $fromnames=null) {
        if ( ! parent::equal_to($other) )               { return false; }
        if ( $this->references != $other->references ) { return false; }
        if ( $this->weak != $other->weak )             { return false; }
        return true;
    }

    function validate($schema,$table) {
        $errors = parent::validate($schema,$table);
        $name = $this->cname ? "Constraint {$table->name}.{$this->cname}" : "Constraint on {$table->name}";
        if ( isset($schema->tables[$this->references['table']]) ) {
            $target = $schema->tables[$this->references['table']];
            if ( count($this->columns) != count($this->references['columns']) ) {
                $errors[] = "$name is on ".
                    $this->pluralize(count($this->columns),'column')." but references ".
                    $this->pluralize(count($this->references['columns']),'column').": column counts must be the same";
            }
            else {
                $sourcecols = array_keys($this->columns);
                foreach ($this->references['columns'] as $i=>$colname) {
                    if ( isset($target->columns[$colname]) ) {
                        $sourcecol = $table->columns[$sourcecols[$i]];
                        $targetcol = $target->columns[$colname];
                        if (! $targetcol->type->equal_to($sourcecol->type)) {
                            $errors[] = "$name's {$table->name}.{$sourcecol->name} type ({$sourcecol->type->to_sql()}) does not match {$target->name}.{$targetcol->name}'s type ({$targetcol->type->to_sql()})";
                        }
                    }
                    else {
                        $errors[] = "$name references {$target->name}.$colname, which does not exist";
                    }
                }
            }
        }
        else {
            $errors[] = "$name references the table {$this->references['table']} which does not exist";
        }
        return $errors;
    }
    function pluralize($num, $counter, $counters=null) {
        if (!isset($counters)) { $counters = $counter . 's'; }
        return "$num " . ($num==1?$counter:$counters);
    }
}
