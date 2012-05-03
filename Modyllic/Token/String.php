<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Strings ('' for everyone, plus "" for MySQL)
 */
class Modyllic_Token_String extends Modyllic_Token {
    function value() {
        return preg_replace('/\r/','',$this->value);
    }
    function unquote() {
        $value = $this->value();
        $quote = $value[0];
        $raw = str_split(substr( $value, 1, -1 ));
        $len = count($raw);
        $unquoted = "";
        for ( $ii=0; $ii<$len; ++$ii ) {
            $chr = $raw[$ii];
            $next = ($ii+1<$len) ? $raw[$ii+1]: '';
            switch ($chr) {
                case '\\':
                    $ii++;
                    switch ($next) {
                        case '0': $unquoted .= chr(0); break;
                        case 'b': $unquoted .= chr(8); break;
                        case 'n': $unquoted .= chr(10); break;
                        case 'r': $unquoted .= chr(13); break;
                        case 't': $unquoted .= chr(9); break;
                        case 'Z': $unquoted .= chr(26); break;
                        // These evalute to themselves plus the backslash--
                        // this is per the MySQL docs and is required for
                        // escaping of % and _ in LIKE clauses to work.
                        case '%':
                        case '_': $unquoted .= $chr.$next; break;
                        // Anything else is just included as itself and has
                        // no special meaning.  This includes quote
                        // characters.
                        default:
                            $unquoted .= $next;
                    }
                    break;
                case $quote: // Two quotes in a row become a single quote
                    if ( $next == $quote ) {
                        $unquoted .= $quote;
                        $ii++;
                        break;
                    }
                default:
                    $unquoted .= $chr;
            }
        }
        return $unquoted;
    }
   function debug() {
       return get_class($this).":".$this->value();
   }
}
