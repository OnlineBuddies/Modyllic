<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * The SQL tokenizer-- this takes one or more SQL commands as a string and
 * splits it into useful chunks.
 */
class Modyllic_Tokenizer {
    private $cmdstr;
    public $pos = 0;
    private $len;
    public $cur;
    private $prev;
                            //      neg/pos   num+decimal     or just dec  optional exponent
    private $quote_chars   = array( "'"=>true, '"'=>true, '`'=>true );
    private $safe_symbol_chars  = array( ','=>true, '('=>true, ')'=>true, '='=>true, '@'=>true, ';'=>true, '!'=>true, '$'=>true, '*'=>true, '+'=>true, ':'=>true, '<'=>true, '>'=>true, '.'=>true, '&'=>true, '|'=>true, '%'=>true );
    private $other_symbol_chars  = array(  '/'=>true, '-'=>true );
    private $ident_re;
    private $reserved_re;

    protected static $on_advance;

    static public function on_advance($todo) {
        self::$on_advance = $todo;
    }

    /**
     * @param string $sql The SQL to tokenize
     */
    public function __construct($sql) {
        $this->cmdstr = $sql;
        $this->len = strlen($this->cmdstr);
        $this->generate_reserved_re();
        $this->ident_re = '/\G('.Modyllic_SQL::$valid_ident_re.')/u';
        $this->cur = new Modyllic_Token_SOC(0);
    }

    private $delimiter = ';';

    /**
     * Set the command delimiter, defaults to ;
     */
    public function set_delimiter($delimiter) {
        $this->delimiter = $delimiter;
    }

    /**
     * Returns the remainder of the current command. The next token
     * will be of type Modyllic_Token_EOC
     */
    public function rest() {
        $rest = "";
        while ( ($cur = $this->rest_next()) !== NULL ) {
            $rest .= $cur;
        }
        $this->inject( new Modyllic_Token_Delim($this->pos) );
        return $rest;
    }

    /**
     * Calculates the number of lines the last token ended on
     */
    public function line() {
        $sofar = substr( $this->cmdstr, 0, $this->pos );
        return preg_match_all( "/\n/u", $sofar, $matches );
    }

    /**
     * Calculates the column that the last token ended at
     */
    public function col() {
        $remaining = substr( $this->cmdstr, 0, $this->pos );
        if (preg_match("/([^\n]*)$/u",$remaining,$matches)) {
            return strlen($matches[1]);
        }
        else {
            return 0;
        }
    }

    /**
     * Provides a couple of lines of context, with <---HERE---> marking the
     * current location of the tokenizer.
     */
    public function context(Modyllic_Token $token=null) {
        if ( !isset($token) ) {
            $token = $this->cur;
        }
        $start_len = $token->pos > 200 ? 200 : $token->pos;
        return substr( $this->cmdstr, $token->pos - $start_len, $start_len ) . "<---HERE--->".
               substr( $this->cmdstr, $token->pos, 200 );
    }

    /**
     * Generate the reserved words regexp, from the list as the bottom of the class.
     */
    private function generate_reserved_re() {
        if ( isset( self::$reserved_words_re ) ) {
            return;
        }
        $reserved = self::reserved_words();
        $strans = array();
        foreach ( $reserved as $word) {
            $strans[] = sprintf("%2d:%s", strlen($word), $word);
        }
        rsort( $strans );
        $reserved = array();
        foreach ( $strans as $word ) {
            $reserved[] = substr($word,3);
        }

        self::$reserved_words_re = '/\G(' . implode('|',$reserved) .')\b/siu';
    }

    private $injected = array();

    /**
     * Injects a token at the head of the token-stream
     */
    public function inject(Modyllic_Token $token) {
        array_unshift($this->injected,$token);
    }

    /**
     * Return the next token without removing it from the token-stream.
     *
     * @param bool $whitespace (default: false) If this is true, whitespace
     * tokens will be returned rather then suppressed.
     */
    function peek_next($whitespace = false) {
        $prev = $this->prev;
        $cur = $this->cur;
        $ws = array();
        do {
            $next = $this->next(true);
            if ($whitespace or ! $next instanceOf Modyllic_Token_Whitespace) {
                break;
            }
            array_unshift($ws, $next);
        } while (1);
        $this->cur = $cur;
        $this->prev = $prev;
        $this->inject($next);
        foreach ( $ws as &$token ) {
            $this->inject($token);
        }
        return $next;
    }

    function is_injected() {
        return $this->injected;
    }
    function is_eof() {
        return $this->pos >= $this->len;
    }
    function is_delimiter() {
        if (!isset($this->delimiter)) return false;
        if ($this->cmdstr{$this->pos} != $this->delimiter{0}) return false;
        return substr($this->cmdstr, $this->pos, strlen($this->delimiter)) == $this->delimiter;
    }
    function is_new_delimiter(&$matches) {
        return $this->prev instanceOf Modyllic_Token_SOC and preg_match( "/\G((DELIMITER(?:\h+(\S+))?)([^\n]*?)(?=\n|\z))/iu", $this->cmdstr, $matches, 0, $this->pos);
    }
    function is_string() {
        return isset( $this->quote_chars[$this->cmdstr[$this->pos]] );
    }
    function is_whitespace(&$match) {
        $cur = $this->pos;
        $len = strlen($this->cmdstr);
        while ($cur < $len and $this->cmdstr{$cur} == ' ' || $this->cmdstr{$cur} == "\t" || $this->cmdstr{$cur} == "\n" || $this->cmdstr{$cur} == "\r" || $this->cmdstr{$cur} == "\v") $cur++;
        if ($cur != $this->pos) {
            $match = substr($this->cmdstr, $this->pos, $cur - $this->pos);
            return true;
        } else {
            $match = null;
            return false;
        }
    }
    function is_reserved(&$matches) {
        return ctype_alpha($this->cmdstr{$this->pos}) and preg_match( self::$reserved_words_re, $this->cmdstr, $matches, 0, $this->pos);
    }
    function is_num(&$match) {
        $cur = $this->pos;
        $digits = 0;
        if ($this->cmdstr{$cur} == '+' or $this->cmdstr{$cur} == '-') $cur++;
        while ($cur < $this->len and ctype_digit($this->cmdstr{$cur})) {
            $cur++;
            $digits++;
        }
        if ($cur < $this->len and $this->cmdstr{$cur} == '.') $cur++;
        while ($cur < $this->len and ctype_digit($this->cmdstr{$cur})) {
            $cur++;
            $digits++;
        }

        if (!$digits) return false;

        if ($cur < $this->len and $this->cmdstr{$cur} == 'e' || $this->cmdstr{$cur} == 'E') {
            $cur++;
            if ($this->cmdstr{$cur} == '+' or $this->cmdstr{$cur} == '-') $cur++;
            if (!ctype_digit($this->cmdstr{$cur})) {
                return false;
            } else {
                while ($cur < $this->len and ctype_digit($this->cmdstr{$cur})) $cur++;
            }
        }

        $match = substr($this->cmdstr, $this->pos, $cur - $this->pos);

        return true;
    }
    function is_ident(&$matches) {
        return preg_match( $this->ident_re, $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_mysql_comment(&$matches) {
        return preg_match( '{\G((/[*]!\d+)\s+.*?)[*]/}su', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_sql_comment(&$matches) {
        return preg_match( '/\G(--(?:\h(.*))?)/u', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_shell_comment(&$matches) {
        return preg_match( '/\G(#(.*))/u', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_c_comment(&$matches) {
        return preg_match( '{\G(/[*](.*?)[*]/)}su', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_safe_symbol() {
        return isset($this->safe_symbol_chars[ $this->cmdstr[$this->pos] ]);
    }
    function is_other_symbol() {
        return isset( $this->other_symbol_chars[$this->cmdstr[$this->pos]] );
    }

    function rest_next() {
        do {
            $redo = false;
            // If any tokens were injected into the head of the stream, we return those immediately
            if ( $this->is_injected() ) {
                $cur = array_shift($this->injected);
                if ( $cur instanceOf Modyllic_Token_EOC ) {
                    return null;
                }
                else {
                    return $cur->literal();
                }
            }

            // If our position is at or past(?!) the end, return EOF
            else if ($this->is_eof()) {
                return null;
            }

            // Match the command delimiter...
            else if ( $this->is_delimiter() ) {
                $this->pos += strlen($this->delimiter);
                return null;
            }
            // If we see a quote character, match a string...
            else if ( $this->is_string() ) {
                $cur = $this->next_string();
                return $cur->literal();
            }
            // MySQL version comment, strip the comment part, but keep the contents
            // These look like: /*!40103 sql */
            // We replace the leading '/*!40103' and trailing '*/' with spaces.
            // Once we've stripped it, we go back through and match tokens again.
            else if ( $this->is_mysql_comment($matches) ) {

                // Zero out the */ at the end of the comment
                $eod = $this->pos + strlen($matches[1]);
                $this->cmdstr[$eod] = ' ';
                $this->cmdstr[$eod+1] = ' ';
                $len = $this->pos+strlen($matches[2]);
                // Clear the /*!#####
                for ( ; $this->pos < $len; $this->pos++ ) {
                    $this->cmdstr[$this->pos] = ' ';
                }
                $redo = true;
            }
            // SQL style comments
            else if ( $this->is_sql_comment($matches) or $this->is_shell_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                return $matches[1];
            }
            // C style comments
            else if ( $this->is_c_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $comment = preg_replace( '/^[*]\s*$|^\s+[*]\s?/mu', '', $matches[2] );
                return $matches[1];
            }
            // Symbol characters
            else if ( $this->is_other_symbol() ) {
                $char = $this->cmdstr[$this->pos];
                $this->pos ++;
                return $char;
            }
            else {
                $delim_first_char = mb_substr($this->delimiter,0,1,'UTF-8');
                if ($delim_first_char == ']' or $delim_first_char == '}') {
                    $delim_first_char = '\\'.$delim_first_char;
                }
                if ( preg_match( "{\G([^-/#\"'$delim_first_char]+)}smu", $this->cmdstr, $matches, 0, $this->pos ) ) {
                    $this->pos += strlen($matches[1]);
                    return $matches[1];
                }
                else {
                    return $this->cmdstr[$this->pos++];
                }
            }
        } while ($redo);
        return null;
    }

    /**
     * Return the next token
     *
     * @param bool $whitespace (default: false) If this is true, whitespace
     * tokens will be returned rather then suppressed.
     */
    function next( $whitespace = false, $peek = false ) {
        $at_eof = $this->cur instanceOf Modyllic_Token_EOF;
        if ( ! $this->cur instanceOf Modyllic_Token_Whitespace and
             ! $this->cur instanceOf Modyllic_Token_Comment ) {
            $this->prev = $this->cur;
        }

        // As we optionally supress whitespace and also mangle the input to
        // handle MySQL conditional comments, we may need to make more then
        // one go at getting a token.  We loop rather then recursing.
        do {
            $redo = false;

            // If any tokens were injected into the head of the stream, we return those immediately
            if ( $this->is_injected() ) {
                $this->cur = array_shift($this->injected);
            }

            // If our position is at or past(?!) the end, return EOF
            else if ($this->is_eof()) {
                $this->cur = new Modyllic_Token_EOF($this->pos);
            }

            // Match the command delimiter...
            else if ( $this->is_delimiter() ) {
                $this->pos += strlen($this->delimiter);
                $this->cur = new Modyllic_Token_Delim($this->pos,$this->delimiter);
            }

            // Symbol characters
            else if ( $this->is_safe_symbol() ) {
                $char = $this->cmdstr[$this->pos];
                $this->pos ++;
                $this->cur = new Modyllic_Token_Symbol($this->pos,$char);
            }
            // If we see a quote character, match a string...
            else if ( $this->is_string() ) {
                $this->cur = $this->next_string();
            }

            // Our simple regexp token matchers...
            else if ( $this->is_whitespace($match) ) {
                $this->pos += strlen($match);
                $this->cur = new Modyllic_Token_Whitespace( $this->pos, $match );
            }
            else if ( $this->is_new_delimiter($matches) ) {
                if ( $matches[3] != '' ) {
                    $this->delimiter = $matches[3];
                }
                $this->pos += strlen($matches[1]);
                if ( $matches[3] == '' ) {
                    $this->inject( new Modyllic_Token_Error_Delimiter($this->pos, $this->line(), $this->col(), $matches[1]) );
                }
                else {
                    if ( preg_match("/\S/u",$matches[4])) {
                        $this->inject( new Modyllic_Token_Error_Delimiter($this->pos, $this->line(), $this->col(), $matches[4]) );
                    }
                    $this->cur = new Modyllic_Token_NewDelim( $this->pos, $matches[2]);
                }
            }
            else if ( $this->is_reserved($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new Modyllic_Token_Reserved($this->pos,$matches[1]);
            }
            else if ( $this->is_num($match) ) {
                $this->pos += strlen($match);
                $this->cur = new Modyllic_Token_Num($this->pos,$match);
            }
            else if ( $this->is_ident($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new Modyllic_Token_Bareword($this->pos,$matches[1]);
            }

            // MySQL version comment, strip the comment part, but keep the contents
            // These look like: /*!40103 sql */
            // We replace the leading '/*!40103' and trailing '*/' with spaces.
            // Once we've stripped it, we go back through and match tokens again.
            else if ( $this->is_mysql_comment($matches) ) {

                // Zero out the */ at the end of the comment
                $eod = $this->pos + strlen($matches[1]);
                $this->cmdstr[$eod] = ' ';
                $this->cmdstr[$eod+1] = ' ';
                $len = $this->pos+strlen($matches[2]);
                // Clear the /*!#####
                for ( ; $this->pos < $len; $this->pos++ ) {
                    $this->cmdstr[$this->pos] = ' ';
                }
                $redo = true;
            }

            // SQL style comments
            else if ( $this->is_sql_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $cmt = isset($matches[2])? trim($matches[2]): "";
                $this->cur = new Modyllic_Token_Comment($this->pos, $matches[1], $cmt );
            }
            // C style comments
            else if ( $this->is_c_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $comment = preg_replace( '/^[*]\s*$|^\s+[*]\s?/mu', '', $matches[2] );
                $this->cur = new Modyllic_Token_Comment($this->pos, $matches[1], trim($comment) );
            }
            // Symbol characters
            else if ( $this->is_other_symbol() ) {
                $char = $this->cmdstr[$this->pos];
                $this->pos ++;
                $this->cur = new Modyllic_Token_Symbol($this->pos,$char);
            }
            // Shell style comments
            else if ( $this->is_shell_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new Modyllic_Token_Comment($this->pos, $matches[1], trim($matches[2]) );
            }
            // Or failing that return an error
            else {
                $badchar = $this->cmdstr[$this->pos];
                $this->pos ++;
                $this->cur = new Modyllic_Token_Error($this->pos, $this->line(), $this->col(), $badchar);
            }

            // Supress whitespace unless we were asked for it
            if ( $this->cur instanceOf Modyllic_Token_Whitespace and ! $whitespace ) {
                $redo = true;
            }

        } while ($redo);

        if ( (!$at_eof and !$peek and ($this->pos % 1000 == 0 or $this->cur instanceOf Modyllic_Token_Except) ) and is_callable(self::$on_advance) ) {
            if ( $this->cur instanceOf Modyllic_Token_EOF ) {
                call_user_func( self::$on_advance, $this->len, $this->len );
            }
            else {
                call_user_func( self::$on_advance, $this->pos, $this->len );
            }
        }

        return $this->cur;
    }

    /**
     * Our string parser, this expects strings to look like <CHR>stuff<CHR>
     * where <CHR> is a quote character.  <CHR> can be escaped by either
     * doubling it or by preceding it with a backslash.  Any character
     * proceeded by a backslash will be included literally.
     * For example, the string: foo's test
     * Could be: 'foo''s test'
     *       Or: 'foo\'s test'
     * If the quote character is ` then it will return a quoted ident
     * token rather then a string token.
     */
    private function next_string() {
        $quote = $this->cmdstr[$this->pos];
        $this->pos ++;
        $str = $quote;
        while ($this->pos < $this->len ) {
            $chr = $this->cmdstr[$this->pos++];
            if ( $chr == '\\' ) {
                $str .= $chr . $this->cmdstr[$this->pos++];
            }
            else if ( $chr == $quote and $this->pos < $this->len and $this->cmdstr[$this->pos] == $quote ) {
                $str .= $chr . $this->cmdstr[$this->pos++];
            }
            else if ( $chr == $quote ) {
                $str .= $chr;
                break;
            }
            else {
                $str .= $chr;
            }
        }
        if ( $quote == '`' ) {
            return new Modyllic_Token_QuotedIdent($this->pos,$str);
        }
        else {
            $token = new Modyllic_Token_String($this->pos,$str);
            // If we're followed by whitespace and a string, then concatenate the string
            if ( $this->peek_next(true) instanceOf Modyllic_Token_Whitespace ) {
                $ws = $this->next(true);
                if ( $this->peek_next(true) instanceOf Modyllic_Token_String ) {
                    $token = new Modyllic_Token_String($this->pos, Modyllic_SQL::quote_str( $token->unquote() . $this->next(false)->unquote() ) );
                }
                else {
                    $this->inject($ws);
                }
            }
            return $token;
        }
    }

    static private $reserved_words_re;
    static private function reserved_words() {
        return array(
            "CALL IN TRANSACTION",
            "CHARACTER SET",
            "CONTAINS SQL",
            "CONTAINS TRANSACTION",
            "CONTINUE HANDLER",
            "DEALLOCATE PREPARE",
            "DISABLE ON SLAVE",
            "DOUBLE PRECISION",
            "END IF",
            "FOR EACH ROW",
            "FOREIGN KEY",
            "IF EXISTS",
            "IF NOT EXISTS",
            "INSERT INTO",
            "MODIFIES SQL DATA",
            "NO ACTION",
            "NO SQL",
            "NO TRANSACTIONS",
            "NOT NULL",
            "NOT DETERMINISTIC",
            "NOT FOUND",
            "ON UPDATE",
            "ON DELETE",
            "ON SCHEDULE",
            "ON COMPLETION",
            "PRIMARY KEY",
            "READS SQL DATA",
            "RENAME TO",
            "SET DEFAULT",
            "SET NULL",
            "START TRANSACTION",
            "SQL SECURITY",
            "TEMPORARY TABLE",
            "TRANSACTION ISOLATION LEVEL",
            );
    }
}


