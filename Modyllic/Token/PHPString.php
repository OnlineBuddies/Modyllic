<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Strings sourced from PHP, not MySQL, thus they're natively unquoted.
 */
class Modyllic_Token_PHPString extends Modyllic_Token_String {
    function value() {
        return Modyllic_SQL::quote_str( $this->literal() );
    }
    function unquote() {
        return $this->literal();
    }
}
