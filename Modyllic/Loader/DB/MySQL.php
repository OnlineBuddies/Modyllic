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
        $dbh->exec("USE information_schema");
        $dbschema = self::selectrow( $dbh, "SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM SCHEMATA WHERE SCHEMA_NAME=?", array($dbname) );
        if ( ! $dbschema ) {
            throw new Exception("Database $dbname does not exist");
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

        if (isset($schema->tables['MODYLLIC'])) {
            $table = Modyllic_Schema_MetaTable::clone_from( $schema->tables['MODYLLIC'] );
            $meta_sth = self::query( $dbh, "SELECT kind,which,value FROM ".Modyllic_SQL::quote_ident($dbname).".MODYLLIC");
            while ( $meta = $meta_sth->fetch(PDO::FETCH_ASSOC) ) {
                $table->add_row( $meta );
            }
            $schema->tables['MODYLLIC'] = $table;
        }
        /// @todo Remove this-- Only keep this around till 0.2.10 or 0.2.11 or so
        else if (isset($schema->tables['SQLMETA'])) {
            $table = Modyllic_Schema_MetaTable::clone_from($schema->tables['SQLMETA']);
            $table->name = 'MODYLLIC';
            unset($schema->tables['SQLMETA']);
            $meta_sth = self::query( $dbh, "SELECT kind,which,value FROM ".Modyllic_SQL::quote_ident($dbname).".SQLMETA");
            while ( $meta = $meta_sth->fetch(PDO::FETCH_ASSOC) ) {
                $table->add_row( $meta );
            }
            $schema->tables['MODYLLIC'] = $table;
        }

        $schema->load_meta();

        // Look for data to load...
        foreach ($schema->tables as $name=>$table) {
            if ( $table->static ) {
                $data_sth = self::query( $dbh, "SELECT * FROM ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($name));
                while ( $data_row = $data_sth->fetch(PDO::FETCH_ASSOC) ) {
                    $table->add_row( $data_row );
                }
            }
        }

    }
}
