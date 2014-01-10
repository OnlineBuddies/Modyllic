<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Class that knows how to construct a schema from a MySQL database
 */
class Modyllic_Loader_DB_MySQL {
    // Static class only
    private function __construct() {}

    static function selectrow( PDO $dbh, $query, array $bind = array() ) {
        $sth = $dbh->prepare( $query );
        foreach ($bind as $key=>$value) {
            $sth->bindValue( $key+1, $value );
        }
        $sth->execute();
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    static function query( PDO $dbh, $query, array $bind = array() ) {
        $sth = $dbh->prepare( $query );
        foreach ($bind as $key=>$value) {
            if ( is_int($key) ) { $key ++; }
            $sth->bindValue( $key, $value );
        }
        $sth->execute();
        return $sth;
    }

    /**
     * @returns Modyllic_Schema
     */
    static function load(PDO $dbh, $dbname, $schema) {
        $dbh->exec("SET NAMES 'UTF8'");
        $dbh->exec("USE information_schema");
        $dbschema = self::selectrow( $dbh, "SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM SCHEMATA WHERE SCHEMA_NAME=?", array($dbname) );
        if ( ! $dbschema ) {
            Modyllic_Status::warn("Database $dbname does not exist\n");
            return;
        }

        $parser = new Modyllic_Parser();

        if ( $schema->name_is_default ) {
            $schema->set_name( $dbschema['SCHEMA_NAME'] );
            $schema->charset = $dbschema['DEFAULT_CHARACTER_SET_NAME'];
            $schema->collate = $dbschema['DEFAULT_COLLATION_NAME'];
        }

        $table_sth = self::query( $dbh, "SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE'", array($dbname) );
        $tables = array();
        while ( $table_row = $table_sth->fetch(PDO::FETCH_ASSOC) ) {
            Modyllic_Status::$source_count ++;
            $table = self::selectrow( $dbh, "SHOW CREATE TABLE ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($table_row['TABLE_NAME']) );
            $tables[$table_row['TABLE_NAME']] = $table['Create Table'];
        }

        $view_sth = self::query( $dbh, "SELECT TABLE_NAME FROM VIEWS WHERE TABLE_SCHEMA=?", array($dbname) );
        $views = array();
        while ( $view_row = $view_sth->fetch(PDO::FETCH_ASSOC) ) {
            Modyllic_Status::$source_count ++;
            $view = self::selectrow( $dbh, "SHOW CREATE VIEW ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($view_row['TABLE_NAME']) );
            $views[$view_row['TABLE_NAME']] = $view['Create View'];
        }

        // Events don't exist in MySQL 5.0
        $events_exist_sth = self::query( $dbh, "SELECT 1 FROM TABLES WHERE TABLE_SCHEMA='information_schema' AND TABLE_NAME='EVENTS'", array() );
        $events_exist = $events_exist_sth->fetch(PDO::FETCH_NUM);
        if ($events_exist) {
            $event_sth = self::query( $dbh, "SELECT EVENT_NAME FROM EVENTS WHERE EVENT_SCHEMA=?", array($dbname) );
            $events = array();
            while ( $event_row = $event_sth->fetch(PDO::FETCH_ASSOC) ) {
                Modyllic_Status::$source_count ++;
                $event = self::selectrow( $dbh, "SHOW CREATE EVENT ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($event_row['EVENT_NAME']) );
                $events[$event_row['EVENT_NAME']] = $event['Create Event'];
            }
        }

        $trigger_sth = self::query( $dbh, "SELECT TRIGGER_NAME FROM TRIGGERS WHERE TRIGGER_SCHEMA=?", array($dbname) );
        $triggers = array();
        while ( $trigger_row = $trigger_sth->fetch(PDO::FETCH_ASSOC) ) {
            Modyllic_Status::$source_count ++;
            $trigger = self::selectrow( $dbh, "SHOW CREATE TRIGGER ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($trigger_row['TRIGGER_NAME']) );
            if (isset($trigger['SQL Original Statement'])) {
                $triggers[$trigger_row['TRIGGER_NAME']] = $trigger['SQL Original Statement'];
            } elseif (isset($trigger['Create Trigger'])) {
                $triggers[$trigger_row['TRIGGER_NAME']] = $trigger['Create Trigger'];
            } else {
                throw new Modyllic_Exception("Cannot determine which field to use for definition of trigger");
            }
        }

        $routine_sth = self::query( $dbh, "SELECT ROUTINE_TYPE, ROUTINE_NAME FROM ROUTINES WHERE ROUTINE_SCHEMA=?", array($dbname) );
        $routines = array();
        while ( $routine = $routine_sth->fetch(PDO::FETCH_ASSOC) ) {
            Modyllic_Status::$source_count ++;
            if ( $routine['ROUTINE_TYPE'] == 'PROCEDURE' ) {
                $proc = self::selectrow( $dbh,"SHOW CREATE PROCEDURE ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($routine['ROUTINE_NAME']) );
                $routines[$routine['ROUTINE_NAME']] = $proc['Create Procedure'];
            }
            else if ( $routine['ROUTINE_TYPE'] == 'FUNCTION' ) {
                $func = self::selectrow( $dbh,"SHOW CREATE FUNCTION ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($routine['ROUTINE_NAME']) );
                $routines[$routine['ROUTINE_NAME']] = $func['Create Function'];
            }
            else {
                throw new Exception("Unknown routine type ".$routine['ROUTINE_TYPE']." for ".$routine['ROUTINE_NAME']);
            }
        }

        foreach ($tables as $table_name=>$table_sql) {
            Modyllic_Status::$source_name = "$dbname.".$table_name;
            $parser->partial( $schema, $table_sql, "$dbname.$table_name" );
            Modyllic_Status::$source_index ++;
        }
        ksort($schema->tables);

        foreach ( $routines as $routine_name=>$routine_sql ) {
            Modyllic_Status::$source_name = "$dbname.$routine_name";
            $parser->partial( $schema, $routine_sql, "$dbname.$routine_name" );
            Modyllic_Status::$source_index ++;
        }
        ksort($schema->routines);

        foreach ($views as $view_name=>$view_sql) {
            Modyllic_Status::$source_name = "$dbname.".$view_name;
            $parser->partial( $schema, $view_sql, "$dbname.$view_name" );
            Modyllic_Status::$source_index ++;
        }
        ksort($schema->views);

        foreach ($events as $event_name=>$event_sql) {
            Modyllic_Status::$source_name = "$dbname.".$event_name;
            $parser->partial( $schema, $event_sql, "$dbname.$event_name" );
            Modyllic_Status::$source_index ++;
        }
        ksort($schema->events);

        foreach ($triggers as $trigger_name=>$trigger_sql) {
            Modyllic_Status::$source_name = "$dbname.".$trigger_name;
            $parser->partial( $schema, $trigger_sql, "$dbname.$trigger_name" );
            Modyllic_Status::$source_index ++;
        }
        ksort($schema->triggers);

        if (isset($schema->tables['MODYLLIC'])) {
            $metatable = $schema->tables['MODYLLIC'] = Modyllic_Schema_MetaTable::create_from($schema->tables['MODYLLIC']);
            $meta_sth = self::query( $dbh, "SELECT kind,which,value FROM ".Modyllic_SQL::quote_ident($dbname).".MODYLLIC");
            while ( $metadata = $meta_sth->fetch(PDO::FETCH_ASSOC) ) {
                $metatable->add_row( $metadata );
            }
        }

        // Load table metadata so we know which tables are static
        $schema->load_meta(array('TABLE'));

        // Look for static tables to load rows from
        foreach ($schema->tables as $name=>$table) {
            if ( !$table->static ) continue;
            $data_sth = self::query( $dbh, "SELECT * FROM ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($name));
            while ( $data_row = $data_sth->fetch(PDO::FETCH_ASSOC) ) {
                $table->add_row( $data_row );
            }
        }

        // Now load the metadata for the rows we just added
        $schema->load_meta(array('ROW'));
    }
}
