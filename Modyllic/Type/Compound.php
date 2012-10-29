<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

abstract class Modyllic_Type_Compound extends Modyllic_Type_String {
    public $values = array();
    function copy_from(Modyllic_Type $old) {
        parent::copy_from($old);
        $this->values = $old->values;
    }
    function to_sql(Modyllic_Type $other=null) {
        $sql = $this->name . "(";
        $valuec = 0;
        foreach ( $this->values as $value ) {
            if ( $valuec ++ ) {
                $sql .= ",";
            }
            $sql .= $value;
        }
        $sql .= ")";
        $sql .= $this->charset_collation($other);
        return $sql;
    }
}
