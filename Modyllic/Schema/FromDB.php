<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/SQL.php";
require_once "Modyllic/Parser.php";
require_once "Modyllic/Schema.php";
require_once "Modyllic/Schema/Loader.php";

/**
 * Class that knows how to construct a schema from a MySQL database
 */
class Modyllic_Schema_FromDB {
    private $dbh;
    /**
     * @param PDO $dbh 
     */
    function __construct($dbh) {
        $this->dbh = $dbh;
    }
    
    function selectrow( $query, array $bind = array() ) {
        $sth = $this->dbh->prepare( $query );
        foreach ($bind as $key=>$value) {
            $sth->bindValue( $key+1, $value );
        }
        $sth->execute();
        return $sth->fetch(PDO::FETCH_ASSOC);
    }
    
    function query( $query, array $bind = array() ) {
        $sth = $this->dbh->prepare( $query );
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
    function get_schema($dbname) {
        $this->dbh->exec("USE information_schema");
        $dbschema = $this->selectrow( "SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM SCHEMATA WHERE SCHEMA_NAME=?", array($dbname) );
        if ( ! $dbschema ) {
            throw new Exception("Database $dbname does not exist");
        }
        
        $parser = new Modyllic_Parser();
        $schema = new Modyllic_Schema();
        
        $schema->name = $dbschema['SCHEMA_NAME'];
        $schema->nameIsDefault = false;
        $schema->charset = $dbschema['DEFAULT_CHARACTER_SET_NAME'];
        $schema->collate = $dbschema['DEFAULT_COLLATION_NAME'];
        
        $table_sth = $this->query( "SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE'", array($dbname) );
        while ( $table_row = $table_sth->fetch(PDO::FETCH_ASSOC) ) {
            Modyllic_Status::$sourceName = "$dbname.".$table_row['TABLE_NAME'];
            $table = $this->selectrow( "SHOW CREATE TABLE `$dbname`.`".$table_row['TABLE_NAME']."`" );
            $parser->partial( $schema, $table['Create Table'], "$dbname.".$table_row['TABLE_NAME'] );
        }
        ksort($schema->tables);
        
        $routine_sth = $this->query( "SELECT ROUTINE_TYPE, ROUTINE_NAME FROM ROUTINES WHERE ROUTINE_SCHEMA=?", array($dbname) );
        while ( $routine = $routine_sth->fetch(PDO::FETCH_ASSOC) ) {
            Modyllic_Status::$sourceName = "$dbname.".$routine['ROUTINE_NAME'];
            if ( $routine['ROUTINE_TYPE'] == 'PROCEDURE' ) {
                $proc = $this->selectrow("SHOW CREATE PROCEDURE `$dbname`.`".$routine['ROUTINE_NAME']."`" );
                $parser->partial( $schema, $proc['Create Procedure'], "$dbname.".$routine['ROUTINE_NAME'] );
            }
            else if ( $routine['ROUTINE_TYPE'] == 'FUNCTION' ) {
                $func = $this->selectrow("SHOW CREATE FUNCTION `$dbname`.`".$routine['ROUTINE_NAME']."`" );
                $parser->partial( $schema, $func['Create Function'], "$dbname.".$routine['ROUTINE_NAME'] );
            }
            else {
                throw new Exception("Unknown routine type ".$routine['ROUTINE_TYPE']." for ".$routine['ROUTINE_NAME']);
            }
        }
        ksort($schema->routines);
        
        if (isset($schema->tables['SQLMETA'])) {
            $table = $schema->tables['SQLMETA'];
            $meta_sth = $this->query("SELECT kind,which,value FROM ".Modyllic_SQL::quote_ident($dbname).".SQLMETA");
            while ( $meta = $meta_sth->fetch(PDO::FETCH_ASSOC) ) {
                $table->add_row( $meta );
            }
        }
        
        $schema->load_sqlmeta();

        // Look for data to load...
        foreach ($schema->tables as $name=>$table) {
            if ( $table->static ) {
                $data_sth = $this->query("SELECT * FROM ".Modyllic_SQL::quote_ident($dbname).".".Modyllic_SQL::quote_ident($name));
                while ( $data_row = $data_sth->fetch(PDO::FETCH_ASSOC) ) {
                    $table->add_row( $data_row );
                }
            }
        }
        
        
        return $schema;
    }
}
