<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/Schema/Loader.php";
require_once dirname(__FILE__)."/Generator.php";
require_once dirname(__FILE__)."/Diff.php";
require_once dirname(__FILE__)."/Commandline.php";

class Modyllic_SQL {
    /**
     * Quote an SQL identifier, but only if we need to.
     * @param string $str
     * @returns string
     */
    static function quote_ident($str,$quote='`') {
        if ( self::valid_ident($str) and ! self::is_reserved($str) ) {
            return $str;
        }
        else {
        $str = str_replace($quote,"$quote$quote", $str );
            return "$quote$str$quote";
        }
    }
    
    /**
     * Quote an SQL string
     * @param string $str
     * @returns string
     */
    static function quote_str($str,$quote="'") {
        $str = str_replace('\\', '\\\\',   $str );
        $str = str_replace(chr(0),"\\0", $str );
        
        $str = str_replace('\\\\%', '\\%', $str );
        $str = str_replace('\\\\_', '\\_', $str );
        $str = str_replace(chr(8),  "\\b", $str );
        $str = str_replace(chr(10), "\\n", $str );
        $str = str_replace(chr(13), "\\r", $str );
        $str = str_replace(chr(9),  "\\t", $str );
        $str = str_replace(chr(26), "\\Z", $str );
        $str = str_replace($quote,"$quote$quote", $str );
        return "$quote$str$quote";
    }
    

    /**
     * @param string $word
     * @returns bool True if $word is a MySQL reserved word
     */
    static function is_reserved($word) {
        static $mysql_reserved = array(
            "ACCESSIBLE" => TRUE,
            "ACTION" => TRUE,
            "ADD" => TRUE,
            "ALL" => TRUE,
            "ALTER" => TRUE,
            "ANALYZE" => TRUE,
            "AND" => TRUE,
            "AS" => TRUE,
            "ASC" => TRUE,
            "ASENSITIVE" => TRUE,
            "BEFORE" => TRUE,
            "BETWEEN" => TRUE,
            "BIGINT" => TRUE,
            "BINARY" => TRUE,
            "BIT" => TRUE,
            "BLOB" => TRUE,
            "BOTH" => TRUE,
            "BY" => TRUE,
            "CALL" => TRUE,
            "CASCADE" => TRUE,
            "CASE" => TRUE,
            "CHANGE" => TRUE,
            "CHAR" => TRUE,
            "CHARACTER" => TRUE,
            "CHECK" => TRUE,
            "COLLATE" => TRUE,
            "COLUMN" => TRUE,
            "COLUMNS" => TRUE,
            "COMMENT" => TRUE,
            "CONDITION" => TRUE,
            "CONNECTION" => TRUE,
            "CONSTRAINT" => TRUE,
            "CONTINUE" => TRUE,
            "CONVERT" => TRUE,
            "CREATE" => TRUE,
            "CROSS" => TRUE,
            "CURRENT_DATE" => TRUE,
            "CURRENT_TIME" => TRUE,
            "CURRENT_TIMESTAMP" => TRUE,
            "CURRENT_USER" => TRUE,
            "CURSOR" => TRUE,
            "DATABASE" => TRUE,
            "DATABASES" => TRUE,
            "DATE" => TRUE,
            "DAY_HOUR" => TRUE,
            "DAY_MICROSECOND" => TRUE,
            "DAY_MINUTE" => TRUE,
            "DAY_SECOND" => TRUE,
            "DEC" => TRUE,
            "DECIMAL" => TRUE,
            "DECLARE" => TRUE,
            "DEFAULT" => TRUE,
            "DELAYED" => TRUE,
            "DELETE" => TRUE,
            "DESC" => TRUE,
            "DESCRIBE" => TRUE,
            "DETERMINISTIC" => TRUE,
            "DISTINCT" => TRUE,
            "DISTINCTROW" => TRUE,
            "DIV" => TRUE,
            "DOUBLE" => TRUE,
            "DROP" => TRUE,
            "DUAL" => TRUE,
            "EACH" => TRUE,
            "ELSE" => TRUE,
            "ELSEIF" => TRUE,
            "ENCLOSED" => TRUE,
            "ENUM" => TRUE,
            "ESCAPED" => TRUE,
            "EXISTS" => TRUE,
            "EXIT" => TRUE,
            "EXPLAIN" => TRUE,
            "FALSE" => TRUE,
            "FETCH" => TRUE,
            "FIELDS" => TRUE,
            "FLOAT" => TRUE,
            "FLOAT4" => TRUE,
            "FLOAT8" => TRUE,
            "FOR" => TRUE,
            "FORCE" => TRUE,
            "FOREIGN" => TRUE,
            "FROM" => TRUE,
            "FULLTEXT" => TRUE,
            "GENERAL" => TRUE,
            "GOTO" => TRUE,
            "GRANT" => TRUE,
            "GROUP" => TRUE,
            "HAVING" => TRUE,
            "HIGH_PRIORITY" => TRUE,
            "HOUR_MICROSECOND" => TRUE,
            "HOUR_MINUTE" => TRUE,
            "HOUR_SECOND" => TRUE,
            "IF" => TRUE,
            "IGNORE" => TRUE,
            "IGNORE_SERVER_IDS" => TRUE,
            "IN" => TRUE,
            "INDEX" => TRUE,
            "INFILE" => TRUE,
            "INNER" => TRUE,
            "INOUT" => TRUE,
            "INSENSITIVE" => TRUE,
            "INSERT" => TRUE,
            "INT" => TRUE,
            "INT1" => TRUE,
            "INT2" => TRUE,
            "INT3" => TRUE,
            "INT4" => TRUE,
            "INT8" => TRUE,
            "INTEGER" => TRUE,
            "INTERVAL" => TRUE,
            "INTO" => TRUE,
            "IS" => TRUE,
            "ITERATE" => TRUE,
            "JOIN" => TRUE,
            "KEY" => TRUE,
            "KEYS" => TRUE,
            "KILL" => TRUE,
            "LABEL" => TRUE,
            "LEADING" => TRUE,
            "LEAVE" => TRUE,
            "LEFT" => TRUE,
            "LIKE" => TRUE,
            "LIMIT" => TRUE,
            "LINEAR" => TRUE,
            "LINES" => TRUE,
            "LOAD" => TRUE,
            "LOCALTIME" => TRUE,
            "LOCALTIMESTAMP" => TRUE,
            "LOCK" => TRUE,
            "LONG" => TRUE,
            "LONGBLOB" => TRUE,
            "LONGTEXT" => TRUE,
            "LOOP" => TRUE,
            "LOW_PRIORITY" => TRUE,
            "MASTER_BIND" => TRUE,
            "MASTER_HEARTBEAT_PERIOD" => TRUE,
            "MASTER_SSL_VERIFY_SERVER_CERT" => TRUE,
            "MATCH" => TRUE,
            "MAXVALUE" => TRUE,
            "MEDIUMBLOB" => TRUE,
            "MEDIUMINT" => TRUE,
            "MEDIUMTEXT" => TRUE,
            "MIDDLEINT" => TRUE,
            "MINUTE_MICROSECOND" => TRUE,
            "MINUTE_SECOND" => TRUE,
            "MOD" => TRUE,
            "MODIFIES" => TRUE,
            "NATURAL" => TRUE,
            "NO" => TRUE,
            "NOT" => TRUE,
            "NO_WRITE_TO_BINLOG" => TRUE,
            "NULL" => TRUE,
            "NUMERIC" => TRUE,
            "ON" => TRUE,
            "ONE_SHOT" => TRUE,
            "OPTIMIZE" => TRUE,
            "OPTION" => TRUE,
            "OPTIONALLY" => TRUE,
            "OR" => TRUE,
            "ORDER" => TRUE,
            "OUT" => TRUE,
            "OUTER" => TRUE,
            "OUTFILE" => TRUE,
            "PARTITION" => TRUE,
            "PRECISION" => TRUE,
            "PRIMARY" => TRUE,
            "PRIVILEGES" => TRUE,
            "PROCEDURE" => TRUE,
            "PURGE" => TRUE,
            "RANGE" => TRUE,
            "READ" => TRUE,
            "READS" => TRUE,
            "READ_ONLY" => TRUE,
            "READ_WRITE" => TRUE,
            "REAL" => TRUE,
            "REFERENCES" => TRUE,
            "REGEXP" => TRUE,
            "RELEASE" => TRUE,
            "RENAME" => TRUE,
            "REPEAT" => TRUE,
            "REPLACE" => TRUE,
            "REQUIRE" => TRUE,
            "RESIGNAL" => TRUE,
            "RESTRICT" => TRUE,
            "RETURN" => TRUE,
            "REVOKE" => TRUE,
            "RIGHT" => TRUE,
            "RLIKE" => TRUE,
            "SCHEMA" => TRUE,
            "SCHEMAS" => TRUE,
            "SECOND_MICROSECOND" => TRUE,
            "SELECT" => TRUE,
            "SENSITIVE" => TRUE,
            "SEPARATOR" => TRUE,
            "SET" => TRUE,
            "SHOW" => TRUE,
            "SIGNAL" => TRUE,
            "SLOW" => TRUE,
            "SMALLINT" => TRUE,
            "SONAME" => TRUE,
            "SPATIAL" => TRUE,
            "SPECIFIC" => TRUE,
            "SQL" => TRUE,
            "SQLEXCEPTION" => TRUE,
            "SQLSTATE" => TRUE,
            "SQLWARNING" => TRUE,
            "SQL_BIG_RESULT" => TRUE,
            "SQL_CALC_FOUND_ROWS" => TRUE,
            "SQL_SMALL_RESULT" => TRUE,
            "SSL" => TRUE,
            "STARTING" => TRUE,
            "STRAIGHT_JOIN" => TRUE,
            "TABLE" => TRUE,
            "TABLES" => TRUE,
            "TERMINATED" => TRUE,
            "TEXT" => TRUE,
            "THEN" => TRUE,
            "TIME" => TRUE,
            "TIMESTAMP" => TRUE,
            "TINYBLOB" => TRUE,
            "TINYINT" => TRUE,
            "TINYTEXT" => TRUE,
            "TO" => TRUE,
            "TRAILING" => TRUE,
            "TRIGGER" => TRUE,
            "TRUE" => TRUE,
            "UNDO" => TRUE,
            "UNION" => TRUE,
            "UNIQUE" => TRUE,
            "UNLOCK" => TRUE,
            "UNSIGNED" => TRUE,
            "UPDATE" => TRUE,
            "UPGRADE" => TRUE,
            "USAGE" => TRUE,
            "USE" => TRUE,
            "USING" => TRUE,
            "UTC_DATE" => TRUE,
            "UTC_TIME" => TRUE,
            "UTC_TIMESTAMP" => TRUE,
            "VALUES" => TRUE,
            "VARBINARY" => TRUE,
            "VARCHAR" => TRUE,
            "VARCHARACTER" => TRUE,
            "VARYING" => TRUE,
            "WHEN" => TRUE,
            "WHERE" => TRUE,
            "WHILE" => TRUE,
            "WITH" => TRUE,
            "WRITE" => TRUE,
            "XOR" => TRUE,
            "YEAR_MONTH" => TRUE,
            "ZEROFILL" => TRUE,
            "_FILENAME" => TRUE,
            // We also need
            "AT" => TRUE,
            "DAY" => TRUE,
            "END" => TRUE,
        );
        return isset($mysql_reserved[strtoupper($word)]);
    }
    
    // Unquoted identifiers are either:  http://dev.mysql.com/doc/refman/5.5/en/identifiers.html
    // An alpha+dollar+underscore followed by any number of digit+alpha+dollar+underscore
    // OR
    // Some number of digits followed a alpha+dollar+underscore followed by any number of digit+alpha+dollar+underscore
    static public $valid_ident_re = '[a-zA-Z$_][0-9a-zA-Z$_]*|[0-9]+[a-zA-Z$_][0-9a-zA-Z$_]*';

    /**
     * @param string $word
     * @returns bool True if $word is a valid unquoted identifier
     */    
    static function valid_ident($word) {
        return preg_match('/^('.SQL::$valid_ident_re.')$/',$word);
    }
}
