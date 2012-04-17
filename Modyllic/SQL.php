<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

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
            "ACCESSIBLE" => true,
            "ACTION" => true,
            "ADD" => true,
            "ALL" => true,
            "ALTER" => true,
            "ANALYZE" => true,
            "AND" => true,
            "AS" => true,
            "ASC" => true,
            "ASENSITIVE" => true,
            "BEFORE" => true,
            "BETWEEN" => true,
            "BIGINT" => true,
            "BINARY" => true,
            "BIT" => true,
            "BLOB" => true,
            "BOTH" => true,
            "BY" => true,
            "CALL" => true,
            "CASCADE" => true,
            "CASE" => true,
            "CHANGE" => true,
            "CHAR" => true,
            "CHARACTER" => true,
            "CHECK" => true,
            "COLLATE" => true,
            "COLUMN" => true,
            "COLUMNS" => true,
            "COMMENT" => true,
            "CONDITION" => true,
            "CONNECTION" => true,
            "CONSTRAINT" => true,
            "CONTINUE" => true,
            "CONVERT" => true,
            "CREATE" => true,
            "CROSS" => true,
            "CURRENT_DATE" => true,
            "CURRENT_TIME" => true,
            "CURRENT_TIMESTAMP" => true,
            "CURRENT_USER" => true,
            "CURSOR" => true,
            "DATABASE" => true,
            "DATABASES" => true,
            "DATE" => true,
            "DAY_HOUR" => true,
            "DAY_MICROSECOND" => true,
            "DAY_MINUTE" => true,
            "DAY_SECOND" => true,
            "DEC" => true,
            "DECIMAL" => true,
            "DECLARE" => true,
            "DEFAULT" => true,
            "DELAYED" => true,
            "DELETE" => true,
            "DESC" => true,
            "DESCRIBE" => true,
            "DETERMINISTIC" => true,
            "DISTINCT" => true,
            "DISTINCTROW" => true,
            "DIV" => true,
            "DOUBLE" => true,
            "DROP" => true,
            "DUAL" => true,
            "EACH" => true,
            "ELSE" => true,
            "ELSEIF" => true,
            "ENCLOSED" => true,
            "ENUM" => true,
            "ESCAPED" => true,
            "EXISTS" => true,
            "EXIT" => true,
            "EXPLAIN" => true,
            "FALSE" => true,
            "FETCH" => true,
            "FIELDS" => true,
            "FLOAT" => true,
            "FLOAT4" => true,
            "FLOAT8" => true,
            "FOR" => true,
            "FORCE" => true,
            "FOREIGN" => true,
            "FROM" => true,
            "FULLTEXT" => true,
            "GENERAL" => true,
            "GOTO" => true,
            "GRANT" => true,
            "GROUP" => true,
            "HAVING" => true,
            "HIGH_PRIORITY" => true,
            "HOUR_MICROSECOND" => true,
            "HOUR_MINUTE" => true,
            "HOUR_SECOND" => true,
            "IF" => true,
            "IGNORE" => true,
            "IGNORE_SERVER_IDS" => true,
            "IN" => true,
            "INDEX" => true,
            "INFILE" => true,
            "INNER" => true,
            "INOUT" => true,
            "INSENSITIVE" => true,
            "INSERT" => true,
            "INT" => true,
            "INT1" => true,
            "INT2" => true,
            "INT3" => true,
            "INT4" => true,
            "INT8" => true,
            "INTEGER" => true,
            "INTERVAL" => true,
            "INTO" => true,
            "IS" => true,
            "ITERATE" => true,
            "JOIN" => true,
            "KEY" => true,
            "KEYS" => true,
            "KILL" => true,
            "LABEL" => true,
            "LEADING" => true,
            "LEAVE" => true,
            "LEFT" => true,
            "LIKE" => true,
            "LIMIT" => true,
            "LINEAR" => true,
            "LINES" => true,
            "LOAD" => true,
            "LOCALTIME" => true,
            "LOCALTIMESTAMP" => true,
            "LOCK" => true,
            "LONG" => true,
            "LONGBLOB" => true,
            "LONGTEXT" => true,
            "LOOP" => true,
            "LOW_PRIORITY" => true,
            "MASTER_BIND" => true,
            "MASTER_HEARTBEAT_PERIOD" => true,
            "MASTER_SSL_VERIFY_SERVER_CERT" => true,
            "MATCH" => true,
            "MAXVALUE" => true,
            "MEDIUMBLOB" => true,
            "MEDIUMINT" => true,
            "MEDIUMTEXT" => true,
            "MIDDLEINT" => true,
            "MINUTE_MICROSECOND" => true,
            "MINUTE_SECOND" => true,
            "MOD" => true,
            "MODIFIES" => true,
            "NATURAL" => true,
            "NO" => true,
            "NOT" => true,
            "NO_WRITE_TO_BINLOG" => true,
            "NULL" => true,
            "NUMERIC" => true,
            "ON" => true,
            "ONE_SHOT" => true,
            "OPTIMIZE" => true,
            "OPTION" => true,
            "OPTIONALLY" => true,
            "OR" => true,
            "ORDER" => true,
            "OUT" => true,
            "OUTER" => true,
            "OUTFILE" => true,
            "PARTITION" => true,
            "PRECISION" => true,
            "PRIMARY" => true,
            "PRIVILEGES" => true,
            "PROCEDURE" => true,
            "PURGE" => true,
            "RANGE" => true,
            "READ" => true,
            "READS" => true,
            "READ_ONLY" => true,
            "READ_WRITE" => true,
            "REAL" => true,
            "REFERENCES" => true,
            "REGEXP" => true,
            "RELEASE" => true,
            "RENAME" => true,
            "REPEAT" => true,
            "REPLACE" => true,
            "REQUIRE" => true,
            "RESIGNAL" => true,
            "RESTRICT" => true,
            "RETURN" => true,
            "REVOKE" => true,
            "RIGHT" => true,
            "RLIKE" => true,
            "SCHEMA" => true,
            "SCHEMAS" => true,
            "SECOND_MICROSECOND" => true,
            "SELECT" => true,
            "SENSITIVE" => true,
            "SEPARATOR" => true,
            "SET" => true,
            "SHOW" => true,
            "SIGNAL" => true,
            "SLOW" => true,
            "SMALLINT" => true,
            "SONAME" => true,
            "SPATIAL" => true,
            "SPECIFIC" => true,
            "SQL" => true,
            "SQLEXCEPTION" => true,
            "SQLSTATE" => true,
            "SQLWARNING" => true,
            "SQL_BIG_RESULT" => true,
            "SQL_CALC_FOUND_ROWS" => true,
            "SQL_SMALL_RESULT" => true,
            "SSL" => true,
            "STARTING" => true,
            "STRAIGHT_JOIN" => true,
            "TABLE" => true,
            "TABLES" => true,
            "TERMINATED" => true,
            "TEXT" => true,
            "THEN" => true,
            "TIME" => true,
            "TIMESTAMP" => true,
            "TINYBLOB" => true,
            "TINYINT" => true,
            "TINYTEXT" => true,
            "TO" => true,
            "TRAILING" => true,
            "TRIGGER" => true,
            "TRUE" => true,
            "UNDO" => true,
            "UNION" => true,
            "UNIQUE" => true,
            "UNLOCK" => true,
            "UNSIGNED" => true,
            "UPDATE" => true,
            "UPGRADE" => true,
            "USAGE" => true,
            "USE" => true,
            "USING" => true,
            "UTC_DATE" => true,
            "UTC_TIME" => true,
            "UTC_TIMESTAMP" => true,
            "VALUES" => true,
            "VARBINARY" => true,
            "VARCHAR" => true,
            "VARCHARACTER" => true,
            "VARYING" => true,
            "WHEN" => true,
            "WHERE" => true,
            "WHILE" => true,
            "WITH" => true,
            "WRITE" => true,
            "XOR" => true,
            "YEAR_MONTH" => true,
            "ZEROFILL" => true,
            "_FILENAME" => true,
            // We also need
            "AT" => true,
            "DAY" => true,
            "END" => true,
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
        return preg_match('/^('.Modyllic_SQL::$valid_ident_re.')$/',$word);
    }
}
