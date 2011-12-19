<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

/**
 * These are what are returned by the tokenizer
 */
class SQL_Token {
    public $pos;
    protected $value;
    function __construct($pos,$value=null) {
        $this->pos = $pos;
        $this->value = $value;
    }
    /**
     * This is exactly the string that was matched by the tokenizer
     */
    function literal() {
        return $this->value;
    }
    /**
     * The value of the token.  This differs from literal for things like
     * comments, which don't include the comment markers and quoted
     * identifiers which have their quotes removed.
     */
    function value() {
        return $this->value;
    }
    /**
     * The "token" value, for any kind of identifier this is all caps.
     */
    function token() {
        return $this->value;
    }
    /**
     * The token value and class, used in debugging
     */
   function debug() {
       return get_class($this).":'".$this->value."'";
   }
}
/**
 * Whitespace chunks, including newlines
 */
class SQL_Token_Whitespace extends SQL_Token {
    function value() {
        return preg_replace('/\r/','',$this->value);
    }
}

/**
 * Identifiers, eg, column names, table names, etc.
 */
class SQL_Token_Ident extends SQL_Token {
    private $upper;
    function token() {
        if ( isset($this->upper) ) {
            return $this->upper;
        }
        else {
            return $this->upper = strtoupper($this->value());
        }
    }
}
/**
 * Identifiers that were quoted (`` in MySQL, "" elsewhere)
 */
class SQL_Token_Quoted_Ident extends SQL_Token_Ident {
    function value() {
        $quote = $this->value[0];
        $unquoted = substr( $this->value, 1, -1 );
        $unquoted = preg_replace("/$quote{2}/", $quote, $unquoted );
        $unquoted = preg_replace('/\\\\(.)/', '$1', $unquoted );
        return $unquoted;
    }
}

/**
 * Reserved words
 */
class SQL_Token_Reserved extends SQL_Token_Ident { }

/**
 * Things that look like numbers
 */
class SQL_Token_Num extends SQL_Token {}

/**
 * Things that look like symbols
 */
class SQL_Token_Symbol extends SQL_Token { }

/**
 * Comments, both C style and SQL style
 */
class SQL_Token_Comment extends SQL_Token {
    protected $literal;
    function __construct( $pos, $literal, $value ) {
        parent::__construct( $pos, $value );
        $this->literal = $literal;
    }
    function value() {
        return preg_replace('/\r/','',$this->value);
    }
    function literal() {
        return preg_replace('/\r/','',$this->literal);
    }
}

/**
 * Strings ('' for everyone, plus "" for MySQL)
 */
class SQL_Token_String extends SQL_Token {
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

/**
 * The tokenizer doesn't produce token lists itself, but parsers may find it
 * useful to clump up a bunch of tokens and reinject them as a single token. 
 * This is here to provide that facility.
 */
class SQL_Token_List extends SQL_Token { }

/**
 * Exception tokens, not generated directly.  These are various non-token
 * results.
 */
class SQL_Token_Except extends SQL_Token {}

/**
 * End of command-- tokens of this class mark the end of an SQL command.
 */
class SQL_Token_EOC extends SQL_Token_Except {}

/**
 * Indicates that we've hit the end of the string that we're parsing
 */
class SQL_Token_EOF extends SQL_Token_EOC {}

/**
 * Indicates that we've encountered a tokenization error.  Tokenization
 * errors do not consume any of the input string and as such, once you hit
 * one it will always be returned by next().
 */
class SQL_Token_Error extends SQL_Token_Except {
    private $row;
    private $col;
    function __construct($pos, $row,$col) {
        $this->pos = $pos;
        $this->row = $row;
        $this->col = $col;
    }
    function value() {
        return "Syntax Error at ".$this->row.", ".$this->col;
    }
}


class SQL_Token_SOC extends SQL_Token_EOC {}

/**
 * Indicates that we found a command delimiter (; by default)
 */
class SQL_Token_Delim extends SQL_Token_SOC {}

class SQL_Token_NewDelim extends SQL_Token_Delim {}

/**
 * The SQL tokenizer-- this takes one or more SQL commands as a string and
 * splits it into useful chunks.
 */
class SQL_Tokenizer {
    private $cmdstr;
    public $pos;
    private $len;
    public $cur;
    private $prev;
                            //      neg/pos   num+decimal     or just dec  optional exponent
    private $num_re        = '/\G ( [+-]?   (?: \d+(?:[.]\d+)? | [.]\d+ ) (?:[Ee][-+]?\d+)? ) \b  /x';
    private $whitespace_re = '/\G(\s+)/';
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
        $this->pos = 0;
        $this->len = strlen($this->cmdstr);
        $this->generate_reserved_re();
        $this->ident_re = '/\G('.SQL::$valid_ident_re.')/';
        $this->cur = new SQL_Token_SOC(0);
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
     * will be of type SQL_Token_EOC
     */
    public function rest() {
        $rest = "";
        while ( ($cur = $this->rest_next()) !== NULL ) {
            $rest .= $cur;
        }
        $this->inject( new SQL_Token_Delim($this->pos) );
        return $rest;
    }
    
    /**
     * Calculates the number of lines the last token ended on
     */
    public function line() {
        $sofar = substr( $this->cmdstr, 0, $this->pos );
        return preg_match_all( "/\n/", $sofar, $matches );
    }
    
    /**
     * Calculates the column that the last token ended at
     */
    public function col() {
        $remaining = substr( $this->cmdstr, 0, $this->pos );
        preg_match("/([^\n]*)$/",$remaining,$matches);
        return strlen($matches[1]);
    }

    /**
     * Provides a couple of lines of context, with <---HERE---> marking the
     * current location of the tokenizer.
     */
    public function context($token=null) {
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
        
        self::$reserved_words_re = '/\G(' . implode('|',$reserved) .')\b/si';
    }
    
    private $injected = array();

    /**
     * Injects a token at the head of the token-stream
     */
    public function inject($token) {
        array_unshift($this->injected,$token);
    }

    /**
     * Return the next token without removing it from the token-stream.
     *
     * @param bool $whitespace (default: FALSE) If this is TRUE, whitespace
     * tokens will be returned rather then suppressed.
     */    
    function peek_next($whitespace = FALSE) {
        $prev = $this->prev;
        $cur = $this->cur;
        $ws = array();
        do {
            $next = $this->next(TRUE);
            if ($whitespace or ! $next instanceOf SQL_Token_Whitespace) {
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
        return isset($this->delimiter) and preg_match( "/\G\Q$this->delimiter\E/", $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_new_delimiter(&$matches) {
        return $this->prev instanceOf SQL_Token_SOC and preg_match( "/\G(DELIMITER\s+(\S+)\s*(?:\n|\z))/i", $this->cmdstr, $matches, 0, $this->pos);
    }
    function is_string() {
        return isset( $this->quote_chars[$this->cmdstr[$this->pos]] );
    }
    function is_whitespace(&$matches) {
        return preg_match( $this->whitespace_re, $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_reserved(&$matches) {
        return preg_match( self::$reserved_words_re, $this->cmdstr, $matches, 0, $this->pos);
    }
    function is_num(&$matches) {
        return preg_match( $this->num_re, $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_ident(&$matches) {
        return preg_match( $this->ident_re, $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_mysql_comment(&$matches) {
        return preg_match( '{\G((/[*]!\d+)\s+.*?)[*]/}s', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_sql_comment(&$matches) {
        return preg_match( '/\G(--(?:[\t ](.*))?)/', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_shell_comment(&$matches) {
        return preg_match( '/\G(#(.*))/', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_c_comment(&$matches) {
        return preg_match( '{\G(/[*](.*?)[*]/)}s', $this->cmdstr, $matches, 0, $this->pos );
    }
    function is_safe_symbol() {
        return isset($this->safe_symbol_chars[ $this->cmdstr[$this->pos] ]);
    }
    function is_other_symbol() {
        return isset( $this->other_symbol_chars[$this->cmdstr[$this->pos]] );
    }
    
    function rest_next() {
        do {
            $redo = FALSE;
            // If any tokens were injected into the head of the stream, we return those immediately
            if ( $this->is_injected() ) {
                $cur = array_shift($this->injected);
                if ( $cur instanceOf SQL_Token_EOC ) {
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
                $redo = TRUE;
            }
            // SQL style comments
            else if ( $this->is_sql_comment($matches) or $this->is_shell_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                return $matches[1];
            }
            // C style comments
            else if ( $this->is_c_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $comment = preg_replace( '/^[*]\s*$|^\s+[*]\s?/m', '', $matches[2] );
                return $matches[1];
            }
            // Symbol characters
            else if ( $this->is_other_symbol() ) {
                $char = $this->cmdstr[$this->pos];
                $this->pos ++;
                return $char;
            }
            
            else if ( preg_match( '{\G([^-/#"\''.$this->delimiter.']+)}sm', $this->cmdstr, $matches, 0, $this->pos ) ) {
                $this->pos += strlen($matches[1]);
                return $matches[1];
            }
            else {
                return $this->cmdstr[$this->pos++];
            }
        } while ($redo);
        return null;
    }

    /**
     * Return the next token
     *
     * @param bool $whitespace (default: FALSE) If this is TRUE, whitespace
     * tokens will be returned rather then suppressed.
     */
    function next( $whitespace = FALSE, $peek = FALSE ) {
        $at_eof = $this->cur instanceOf SQL_Token_EOF;
        if ( ! $this->cur instanceOf SQL_Token_Whitespace and
             ! $this->cur instanceOf SQL_Token_Comment ) {
            $this->prev = $this->cur;
        }
        
        // As we optionally supress whitespace and also mangle the input to
        // handle MySQL conditional comments, we may need to make more then
        // one go at getting a token.  We loop rather then recursing.
        do {
            $redo = FALSE;
            
            // If any tokens were injected into the head of the stream, we return those immediately
            if ( $this->is_injected() ) {
                $this->cur = array_shift($this->injected);
            }
            
            // If our position is at or past(?!) the end, return EOF
            else if ($this->is_eof()) {
                $this->cur = new SQL_Token_EOF($this->pos);
            }
            
            // Match the command delimiter...
            else if ( $this->is_delimiter() ) {
                $this->pos += strlen($this->delimiter);
                $this->cur = new SQL_Token_Delim($this->pos,$this->delimiter);
            }
            
            // Symbol characters
            else if ( $this->is_safe_symbol() ) {
                $char = $this->cmdstr[$this->pos];
                $this->pos ++;
                $this->cur = new SQL_Token_Symbol($this->pos,$char);
            }
            // If we see a quote character, match a string...
            else if ( $this->is_string() ) {
                $this->cur = $this->next_string();
            }
            
            // Our simple regexp token matchers...
            else if ( $this->is_whitespace($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new SQL_Token_Whitespace( $this->pos, $matches[1] );
            }
            else if ( $this->is_new_delimiter($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->delimiter = $matches[2];
                $this->cur = new SQL_Token_NewDelim( $this->pos, $matches[1]);
            }
            else if ( $this->is_reserved($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new SQL_Token_Reserved($this->pos,$matches[1]);
            }
            else if ( $this->is_num($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new SQL_Token_Num($this->pos,$matches[1]);
            }
            else if ( $this->is_ident($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new SQL_Token_Ident($this->pos,$matches[1]);
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
                $redo = TRUE;
            }
            
            // SQL style comments
            else if ( $this->is_sql_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $cmt = isset($matches[2])? trim($matches[2]): "";
                $this->cur = new SQL_Token_Comment($this->pos, $matches[1], $cmt );
            }
            // C style comments
            else if ( $this->is_c_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $comment = preg_replace( '/^[*]\s*$|^\s+[*]\s?/m', '', $matches[2] );
                $this->cur = new SQL_Token_Comment($this->pos, $matches[1], trim($comment) );
            }
            // Symbol characters
            else if ( $this->is_other_symbol() ) {
                $char = $this->cmdstr[$this->pos];
                $this->pos ++;
                $this->cur = new SQL_Token_Symbol($this->pos,$char);
            }
            // Shell style comments
            else if ( $this->is_shell_comment($matches) ) {
                $this->pos += strlen($matches[1]);
                $this->cur = new SQL_Token_Comment($this->pos, $matches[1], trim($matches[2]) );
            }
            // Or failing that return an error
            else {
                $this->cur = new SQL_Token_Error($this->pos,$this->line(), $this->col());
            }
            
            // Supress whitespace unless we were asked for it
            if ( $this->cur instanceOf SQL_Token_Whitespace and ! $whitespace ) {
                $redo = TRUE;
            }
            
        } while ($redo);

/*
        $literal = preg_replace('/\n/','\n',$this->cur->literal());
        $literal = preg_replace('/(^\s+.*|.*\s+$)/','\'$1\'', $literal);
        print sprintf("%6d",$this->pos) . ($whitespace ? " WS " : "    " )."- ".get_class($this->cur).": $literal\n";
*/
        if ( (!$at_eof and !$peek and ($this->pos % 1000 == 0 or $this->cur instanceOf SQL_Token_Except) ) and is_callable(self::$on_advance) ) {
            if ( $this->cur instanceOf SQL_Token_EOF ) {
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
            return new SQL_Token_Quoted_Ident($this->pos,$str);
        }
        else {
            $token = new SQL_Token_String($this->pos,$str);
            // If we're followed by whitespace and a string, then concatenate the string
            if ( $this->peek_next(TRUE) instanceOf SQL_Token_Whitespace ) {
                $ws = $this->next(TRUE);
                if ( $this->peek_next(TRUE) instanceOf SQL_Token_String ) {
                    $token = new SQL_Token_String($this->pos, SQL::quote_str( $token->unquote() . $this->next(FALSE)->unquote() ) );
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
            "ALGORITHM",
            "ALIASES", // Special
            "ALTER",
            "AUTO_INCREMENT",
            "ARCHIVE",
            "ARGS", // Special
            "ASCII",
            "AT",
            "BINARY",
            "BEGIN",
            "BIGINT",
            "BINARY",
            "BIT",
            "BOOL",
            "BOOLEAN",
            "BLACKHOLE",
            "BLOB",
            "BTREE",
            "CALL",
            "CALL IN TRANSACTION",
            "CASCADE",
            "CHAR",
            "CHARACTER SET",
            "CHARSET",
            "CREATE",
            "COLLATE",
            "COLUMN",
            "COMMENT",
            "COMMIT",
            "COMMITTED",
            "CONCAT",
            "CONSTRAINT",
            "CONTAINS SQL",
            "CONTAINS TRANSACTION",
            "CONTINUE HANDLER",
            "CURRENT_TIMESTAMP",
            "CURRENT_USER",
            "CSV",
            "DATABASE",
            "DATE",
            "DATETIME",
            "DAY",
            "DAY_HOUR",
            "DAY_MINUTE",
            "DAY_SECOND",
            "DEALLOCATE PREPARE",
            "DEC",
            "DECIMAL",
            "DEFAULT",
            "DEFINER",
            "DELIMITER",
            "DETERMINISTIC",
            "DISABLE",
            "DISABLE ON SLAVE",
            "DO",
            "DOUBLE",
            "DOUBLE PRECISION",
            "DROP",
            "DUPLICATE",
            "ENABLE",
            "END",
            "ENDS",
            "END IF",
            "ENGINE",
            "ENUM",
            "EVENT",
            "EVERY",
            "EXECUTE",
            "FEDERATED",
            "FIXED",
            "FLOAT",
            "FULLTEXT",
            "FUNCTION",
            "FOREIGN KEY",
            "GEOMETRY",
            "HASH",
            "HEAP",
            "HOUR",
            "HOUR_MINUTE",
            "HOUR_SECOND",
            "IF EXISTS",
            "IN",
            "INDEX",
            "INNODB",
            "INOUT",
            "INSERT INTO",
            "INT",
            "INTEGER",
            "INTERVAL",
            "INVOKER",
            "KEY",
            "LIST",
            "LONGBLOB",
            "LONGTEXT",
            "MAP",
            "MEDIUMBLOB",
            "MEDIUMINT",
            "MEDIUMTEXT",
            "MEMORY",
            "MERGE",
            "MINUTE",
            "MINUTE_SECOND",
            "MODIFIES SQL DATA",
            "MONTH",
            "MYISAM",
            "NATIONAL",
            "NDBCLUSTER",
            "NUMERIC",
            "NO ACTION",
            "NO SQL",
            "NO TRANSACTIONS",
            "NONE",
            "NOT",
            "NOT NULL",
            "NOT DETERMINISTIC",
            "NOT FOUND",
            "NOW",
            "NULL",
            "ON",
            "ON UPDATE",
            "ON DELETE",
            "ON SCHEDULE",
            "ON COMPLETION",
            "OUT",
            "PREPARE",
            "PRESERVE",
            "PRIMARY KEY",
            "PROCEDURE",
            "QUARTER",
            "READS SQL DATA",
            "REAL",
            "REFERENCES",
            "RENAME TO",
            "RESTRICT",
            "RETURN",
            "RETURNS",
            "ROW",
            "SECOND",
            "SERIAL",
            "SET",
            "SET DEFAULT",
            "SET NULL",
            "SIGNED",
            "SMALLINT",
            "SPATIAL",
            "START TRANSACTION",
            "STARTS",
            "SQL SECURITY",
            "STH",
            "TABLE",
            "TEMPORARY TABLE",
            "TEMPTABLE",
            "TEXT",
            "TIME",
            "TIMESTAMP",
            "TINYBLOB",
            "TINYINT",
            "TINYTEXT",
            "TRANSACTION ISOLATION LEVEL",
            "TRUNCATE",
            "UNDEFINED",
            "UNICODE",
            "UNIQUE",
            "UNSIGNED",
            "UNTIL",
            "UPDATE",
            "USE",
            "USING",
            "VALUES",
            "VARCHAR",
            "VARBINARY",
            "VIEW",
            "WEAKLY",
            "WEEK",
            "YEAR",
            "YEAR_MONTH",
            "ZEROFILL",
            );
    }
}


