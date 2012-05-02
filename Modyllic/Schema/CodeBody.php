<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Schema_CodeBody extends Modyllic_Diffable {
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

    function equal_to(Modyllic_Schema_CodeBody $other) {
        if ( get_class($other) != get_class($this) )   { return false; }
        if ( $this->_body_no_comments() != $other->_body_no_comments() ) { return false; }
        return true;
    }

}
