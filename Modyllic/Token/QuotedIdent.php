<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Identifiers that were quoted (`` in MySQL, "" elsewhere)
 */
class Modyllic_Token_QuotedIdent extends Modyllic_Token implements Modyllic_Token_Ident {
    function value() {
        $quote = $this->value[0];
        $unquoted = substr( $this->value, 1, -1 );
        $unquoted = preg_replace("/\Q$quote\E{2}/u", $quote, $unquoted );
        $unquoted = preg_replace('/\\\\(.)/u', '$1', $unquoted );
        return $unquoted;
    }
    function is_ident() {
        return true;
    }
}
