<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/Tokenizer.php";
require_once dirname(__FILE__)."/Schema.php";

/**
 * Our SQL parser, this is intimately involved
 */
class Modyllic_Parser {
    private $schema;

    private $filename;

    private $ctx; // The current object that we're creating/altering
                  // Be it table, routine or event

    private $tok; // An instance of the tokenizer
    
    /**
     * This parses the SQL in $sql returns an Modyllic_Schema object.
     *
     * @param string $sql
     * @param string $filename Optionally, the filename this SQL was loaded from, used in error messages
     * @returns array
     */
    function parse($sql,$filename="SQL") {

        return $this->partial( new Modyllic_Schema(), $sql, $filename, ";" );
    }
    
    /**
     * A partial parse, this differs from a full parse in two key ways, first,
     * it doesn't make it's own Modyllic_Schema object and second, it allows you to
     * predeclare the delimiter, by default no delimiter is allowed-- that is,
     * it will parse only one command.
     *
     * @param Modyllic_Schema $schema
     * @param string $sql
     * @param string $filename Optionally, the filename this SQL was loaded from, used in error messages
     * @param string $delim (default: "")
     * @returns array
     */
    function partial($schema, $sql, $filename="SQL", $delim=null) {
        $this->filename = $filename;
        $this->tok = new Modyllic_Tokenizer( $sql );
        $this->tok->set_delimiter( $delim );

        $this->schema = $schema;
        
        try {
            while ( 1 ) {
                $this->parse_command();
                if ( $this->next() instanceOf Modyllic_Token_Delim ) {
                    continue;
                }
                else if ( $this->cur() instanceOf Modyllic_Token_EOF ) {
                    break;
                }
                else {
                    throw $this->error("Extra tokens after end of command: ".$this->cur()->debug());
                }
            }
        }
        catch (Exception $e) {
            if ( $e instanceOf Modyllic_Exception ) {
                throw $e;
            }
            else {
                throw $this->error($e->getMessage());
            }
        }
        return $this->schema;
    }
    
    private $cmddocs;

    /**
     * Proxy to the tokenizer's next method
     */
    function next($whitespace=false) {
        $next = $this->tok->next($whitespace);
        if ( $next instanceOf Modyllic_Token_Error ) {
            throw $this->error( "Syntax error" );
        }
        return $next;
    }
    
    /**
     * Returns the last token returned by next()-- eg, the current token
     * being processed.
     */
    function cur() {
        return $this->tok->cur;
    }
    
    /**
     * Returns all of the remainder of the current command as a string.
     */
    function rest() {
        return $this->tok->rest();
    }
    /**
     * Proxy to the tokenizer's next method
     */ 
    function peek_next($whitespace=false) {
        return $this->tok->peek_next($whitespace);
    }

    /**
     * Parse an SQL command
     */
    function parse_command() {
        $this->cmddocs = "";

        // Any leading comments are the documentation for the command to come
        while ( $this->next() instanceOf Modyllic_Token_Comment ) {
            $this->cmddocs = trim( $this->cmddocs . " " . $this->cur()->value() );
        }
        // Empty commands are valid
        if ( $this->cur() instanceOf Modyllic_Token_EOC ) {
            $this->tok->inject($this->cur());
            return;
        }
        
        // We look for our command name as a cmd_NAME method on the current class
        $this->assert_reserved();
        $method = str_replace(" ","_", "cmd_". $this->cur()->token() );
        if ( is_callable( array($this,$method) ) ) {
            $this->$method();
        }
        else {
            throw $this->error("Unsupported SQL command ".$this->cur()->debug());
        }
    }

    function cmd_CREATE() {
        // CREATE <OPTS> <THING>
        // <OPTS> = [DEFINER = CURRENT_USER|username@hostname] 
        //          [ALGORITHM=UNDEFINED|MERGE|TEMPTABLE] 
        //          [SQL SECURITY=DEFINER|INVOKER]
        // <THING> = <RESERVED>

        // Create commands can take a bunch of options before getting around to telling us what they're creating.
        // Right now we ignore the values of all of these, so we just note them and ignore them.
        while (1) {
            $name = $this->get_reserved();
            if ( $name == "DEFINER" ) {
                $this->get_symbol( "=" );
                if ( $this->next()->token() == "CURRENT_USER" ) {
                    $this->assert_reserved();
                }
                else {
                    $this->assert_ident();
                    $this->get_symbol('@');
                    $this->get_ident();
                }
            }
            else if ( $name == "ALGORITHM" ) {
                $this->get_symbol( "=" );
                $this->get_reserved(array("UNDEFINED","MERGE","TEMPTABLE"));
            }
            else if ( $name == "SQL SECURITY" ) {
                $this->get_reserved(array("DEFINER","INVOKER"));
            }
            else {
                break;
            }
        }
            
        // The rest here works pretty much the way parse_command works, finding and calling a method
        $method = str_replace(" ","_", "cmd_CREATE_$name");
        if ( is_callable( array($this,$method) ) ) {
            $this->$method();
        }
        else {
            throw $this->error( "Unsupported SQL command CREATE ".$this->cur()->debug());
        }
    }
    
    function cmd_TRUNCATE() {
        // TRUNCATE [TABLE] tbl_name
        $this->maybe('TABLE');
        $table_name = $this->get_ident();
        if ( ! isset($this->schema->tables[$table_name]) ) {
            throw $this->error( "Can't TRUNCATE table $table_name before it is CREATEd" );
        }
        $this->schema->tables[$table_name]->clear_data();
    }
    
    function cmd_INSERT_INTO() {
        // INSERT INTO tbl_name row_data
        // row_data:
        //     (col_name,...) VALUES (value,...)
        //   | SET col_name=value, ... 
        $table_name = $this->get_ident();
        if ( ! isset($this->schema->tables[$table_name]) ) {
            throw $this->error( "Can't INSERT INTO table $table_name before it is CREATEd" );
        }
        $table = $this->schema->tables[$table_name];
        $row = array();
        if ( $this->maybe('(') ) {
            $this->assert_symbol();
            $columns = $this->get_array();
            $this->get_reserved('VALUES');
            $this->get_symbol('(');
            $values = $this->get_token_array();
            if ( count($columns) != count($values) ) {
                throw $this->error("INSERT INTO column count doesn't match value count" );
            }
            $row = array_combine( $columns, $values );
        }
        else if ( $this->maybe('SET') ) {
           $this->assert_reserved();
            while ( ! $this->peek_next() instanceOf Modyllic_Token_EOC ) {
                $this->maybe(',');
                $col = $this->get_ident();
                $this->get_symbol('=');
                $value = $this->next();
                $row[$col] = $value;
            }
        }
        else {
            throw $this->error( "Expected '(col_names) VALUES (values)' or 'SET col_name=value,...'" );
        }
        $table->add_row( $row );
    }
    
    function cmd_USE() {
        $name = $this->get_ident();
        if ( $this->schema->name == Modyllic_Schema::DEFAULT_NAME ) {
            $this->schema->name = $name;
        }
        if ( $name != $this->schema->name ) {
            throw $this->error( "Can't USE $name when creating ".$this->schema->name );
        }
        $this->rest();
    }

    function cmd_DROP() {
        $what = $this->get_reserved();
        $this->maybe('IF EXISTS');
        $name = $this->get_ident();
        switch ($what) {
            case 'TABLE':
                unset($this->schema->tables[$name]);
                break;
            case 'INDEX':
                $this->get_reserved('ON');
                $table_name = $this->get_ident();
                if ( isset($this->schema->tables[$table_name]) ) {
                    $table = $this->schema->tables[$table_name];
                    if ( isset($table->indexes[$name]) ) {
                        unset($table->indexes[$name]);
                    }
                    else if ( isset($table->indexes["~$name"]) ) {
                        unset($table->indexes["~$name"]);
                    }
                    else {
                        throw $this->error("Can't drop index $table_name.$name as $name does not exist");
                    }
                }
                else {
                    throw $this->error("Can't drop INDEX on $table_name as $table_name does not exist");
                }
                break;
            case 'EVENT':
                unset($this->schema->events[$name]);
                break;
            case 'PROCEDURE':
            case 'FUNCTION':
                unset($this->schema->routines[$name]);
                break;
            case 'SCHEMA':
            case 'DATABASE':
                $this->schema = new Modyllic_Schema();
                break;
            case 'VIEW':
                unset($this->schema->views[$name]);
                break;
            default:
                throw $this->error( "Don't know how to drop a $what" );
        }
    }
    
    function cmd_SET() {
        // Ignore sets, again, only from dumps
        $this->rest();
    }
    function cmd_UPDATE() {
        error_log("-- Ignoring UPDATE ".$this->rest());
    }
    function cmd_CALL() {
        error_log("-- Ignoring CALL ".$this->rest());
    }
    
    function cmd_ALTER() {
        $name = $this->get_ident();
        $method = str_replace(" ","_", "cmd_ALTER_$name");
        if ( is_callable( array($this,$method) ) ) {
            $this->$method();
        }
        else {
            throw $this->error( "Unsupported SQL command ALTER ".$this->cur()->debug());
        }
    }
    
    function cmd_ALTER_SCHEMA() {
        cmd_ALTER_DATABASE();
    }
    
    function cmd_ALTER_DATABASE() {
        $name = $this->get_ident();
        if ( $this->schema->name == Modyllic_Schema::DEFAULT_NAME ) {
            $this->schema->name = $name;
        }
        if ( $name != $this->schema->name ) {
            throw $this->error( "Can't ALTER $name when creating ".$this->schema->name );
        }
        $this->get_create_specification();
    }
    
    function cmd_ALTER_TABLE() {
        $table_name = $this->get_ident();
        if ( isset($this->schema->tables[$table_name]) ) {
            $table = $this->schema->tables[$table_name];
        }
        else {
            throw $this->error("Can't alter $table_name as $table_name does not exist");
        }
        while ( ! ($this->next() instanceOf Modyllic_Token_EOC) ) {
            if ( $this->maybe_table_option() ) { }
            else if ( $this->cur()->token() == "DROP" ) {
                $this->get_reserved();
                if ( $this->cur()->token() == "PRIMARY KEY" ) {
                    if ( isset($table->indexes["!PRIMARY KEY"]) ) {
                        unset($table->indexes["!PRIMARY KEY"]);
                    }
                    else {
                        throw $this->error("Can't drop primary key as there isn't one currently");
                    }
                }
                else if ( $this->cur()->token() == "FOREIGN KEY" ) {
                    $name = $this->get_ident();
                    if ( isset($table->indexes["~$name"]) ) {
                        unset($table->indexes["~$name"]);
                    }
                    else {
                        throw $this->error("Can't drop foreign key constraint $table_name.$name as $name does not exist");
                    }
                }
                else if ( $this->cur()->token() == "INDEX" or $this->cur()->token() == "KEY" ) {
                    $name = $this->get_ident();
                    if ( isset($table->indexes[$name]) ) {
                        unset($table->indexes[$name]);
                    }
                    else {
                        throw $this->error("Can't drop index $table_name.$name as $name does not exist");
                    }
                }
                else if ( $this->cur()->token() == "COLUMN" or $this->cur() instanceOf Modyllic_Token_Ident ) {
                    if ( $this->cur() instanceOf Modyllic_Token_Ident ) {
                        $name = $this->cur()->value();
                    }
                    else {
                        $name = $this->get_ident();
                    }
                    if ( isset($table->columns[$name]) ) {
                        unset($table->columns[$name]);
                    }
                    else {
                        throw $this->error("Can't drop column $table_name.$name as $name does not exist");
                    }
                }
                else {
                    throw $this->error("Don't know how to DROP ".$this->cur()->debug());
                }
            }
            else {
                throw $this->error("Unknown token in ALTER TABLE $table_name");
            }
        }
    }
    
    function cmd_CREATE_SCHEMA() {
        cmd_CREATE_DATABASE();
    }

    function cmd_CREATE_DATABASE() {
        // CREATE {DATABASE | SCHEMA} db_name [create_specification] ...
        // create_specification:
        //      [DEFAULT] {CHARACTER SET | CHARSET} [=] charset_name
        //    | [DEFAULT] COLLATE [=] collation_name
        $this->maybe('IF NOT EXISTS');
        $this->schema->name = $this->get_ident();
        $this->get_create_specification();
    }
    
    function updated_collate( $old, $new, $collate ) {
        return preg_replace( "/^\Q$old\E/", $new, $collate );
    }
    
    function get_create_specification() {
        while ( ! $this->peek_next() instanceOf Modyllic_Token_EOC ) {
            $attr = $this->get_reserved(array("DEFAULT", "CHARACTER SET", "CHARSET", "COLLATE"));
            if ( $attr == "DEFAULT" ) {
                $attr = $this->get_reserved(array("CHARACTER SET", "CHARSET", "COLLATE"));
            }
            $this->maybe('=');
            $value = $this->get_ident();
            if ( $attr == "COLLATE" ) {
                $this->schema->collate = $value;
            }
            else { // It must be the character set
                $this->schema->collate = $this->updated_collate( $this->schema->charset, $value, $this->schema->collate );
                $this->schema->charset = $value;
            }
        }
    }
    
    function cmd_ALTER_EVENT() {
        $name = $this->get_ident();
        if ( ! isset( $this->schema->events[$name] ) ) {
            throw $this->error("Can't ALTER EVENT $name as we haven't seen a CREATE EVENT for it yet");
        }
        $this->ctx = $this->schema->events[$name];
        if ( $this->maybe('ON SCHEDULE') ) {
            $this->assert_reserved();
            $this->get_schedule();
        }
        if ( $this->maybe('ON COMPLETION') ) {
            $this->assert_reserved();
            $this->get_completion();
        }
        if ( $this->maybe('RENAME TO') ) {
            $this->assert_reserved();
            $new_name = $this->get_ident();
            unset($this->schema->events[$name]);
            $this->ctx->name = $new_name;
            $this->add_event( $this->ctx );
        }
        if ( $this->maybe(array('ENABLE','DISABLE','DISABLE ON SLAVE')) ) {
            $this->assert_reserved();
        }
        if ( $this->maybe('DO') ) {
            $this->assert_reserved();
            $this->get_event_body();
        }
    }
    
    function cmd_CREATE_EVENT() {
        $this->ctx = $this->schema->add_event( new Modyllic_Event( $this->get_ident() ) );
        $this->get_reserved('ON SCHEDULE');
        $this->get_schedule();
        if ( $this->maybe('ON COMPLETION') ) {
            $this->assert_reserved();
            $this->get_completion();
        }
        if ( $this->maybe(array('ENABLE','DISABLE','DISABLE ON SLAVE')) ) {
            $this->ctx->status = $this->assert_reserved();
        }
        $this->get_reserved('DO');
        $this->get_event_body();
        $this->ctx = null;
    }
    
    function get_event_body() {
        if ( $this->peek_next()->token() == "BEGIN" ) {
            $this->ctx->body = "\n".trim($this->rest());
        }
        else if ( $this->peek_next()->token() == "CALL" ) {
            $this->ctx->body = trim($this->rest());
        }
        else {
            $this->ctx->body = "\nBEGIN\n    " . trim($this->rest()) . ";\nEND";
        }
    }
    
    function get_schedule() {
        // ON SCHEDULE schedule
        // schedule:
        //   AT timestamp [+ INTERVAL interval] ...
        // | EVERY interval
        //   [STARTS timestamp [+ INTERVAL interval] ...]
        //   [ENDS timestamp [+ INTERVAL interval] ...]
        $this->ctx->schedule = '';
        $term = array( 
            "DO", "ON COMPLETION", "ENABLE", "DISABLE",
            "DISABLE ON SLAVE",
            );
        if ( $this->peek_next()->token() == "AT" ) {
            $this->ctx->schedule .= $this->get_reserved() . 
                              $this->get_expression( $term );
        }
        else if ( $this->peek_next()->token() == "EVERY" ) {
            $this->ctx->schedule .= $this->get_reserved() .
                              $this->get_expression( $term + array("STARTS","ENDS") );
            if ( $this->peek_next()->token() == "STARTS" ) {
                $this->ctx->schedule .= " " . $this->get_reserved() . 
                                  $this->get_expression( $term + array("ENDS") );
            }
            if ( $this->peek_next()->token() == "ENDS" ) {
                $this->ctx->schedule .= " " . $this->get_reserved() . 
                                  $this->get_expression( $term );
            }
        }
        else {
            throw $this->error("Expected AT or EVERY in event schedule");
        }
    }
    
    function get_completion() {
        // [ON COMPLETION [NOT] PRESERVE]
        $in_schedule = FALSE;
        if ( $this->next()->token() == "NOT" ) {
            $this->assert_reserved();
            $this->ctx->preserve = FALSE;
            $this->next();
        }
        else {
            $this->ctx->preserve = TRUE;
        }
        $this->assert_reserved("PRESERVE");
    }
    
    function get_expression($term) {
        $expr = " ";
        while ( ! $this->peek_next(true) instanceOf Modyllic_Token_EOC and
                ! in_array($this->peek_next(true)->token(),$term) ) {
            $expr .= $this->next(true)->value();
        }
        return $expr;
    }
    
    function cmd_CREATE_VIEW() {
        $name = $this->get_ident();
        if ( isset($this->schema->tables[$name]) ) {
            throw $this->error("Can't create VIEW $name when a table of that name already exists");
        }
        $view = $this->schema->add_view( new Modyllic_View( $name ) );
        ## Minimal support for views currently
        $view->def = $this->rest();
    }
    
    function cmd_CREATE_PROCEDURE() {
        // CREATE PROCEDURE sp_name ([proc_parameter[,...]]) 
        // [RETURNS {ROW|COLUMN colname|MAP (key,val)|STH|TABLE|NONE}]
        // [characteristic ...] routine_body
        $proc = $this->schema->add_routine( new Modyllic_Proc( $this->get_ident() ) );
        $proc->args = $this->get_args();
        $proc->docs = $this->cmddocs;
        $proc->returns = array('type'=>'NONE');
        while ( $this->maybe(array('RETURNS','ARGS')) ) {
            switch ($this->assert_reserved()) {
            case 'RETURNS':
                $proc->returns = array();
                switch ($proc->returns['type'] = $this->get_reserved(array('ROW','COLUMN','MAP','STH','TABLE','LIST','NONE'))) {
                case 'COLUMN':
                    $proc->returns['column'] = $this->get_ident();
                    break;
                case 'LIST':
                    $proc->returns['column'] = $this->get_ident();
                    break;
                case 'MAP':
                    $this->get_symbol('(');
                    $values = $this->get_array();
                    if ( count($values) != 2 ) {
                        throw $this->error("MAP proc return type must have two arguments");
                    }
                    $proc->returns['key'] = $values[0];
                    $proc->returns['value'] = $values[1];
                    break;
                }
                break;
            case 'ARGS':
                $proc->args_type = $this->get_reserved();
                break;
            }
        }
        $this->load_routine_body($proc);
    }
    
    function cmd_CREATE_FUNCTION() {
        // CREATE FUNCTION sp_name ([proc_parameter[,...]]) RETURNS type [characteristic ...] routine_body
        $func = $this->schema->add_routine( new Modyllic_Func( $this->get_ident() ) );
        $func->args = $this->get_args();
        $func->docs = $this->cmddocs;
        $this->get_reserved('RETURNS');
        $func->returns = $this->get_type();
        if ( $this->maybe('ARGS') ) {
            $proc->args_type = $this->get_reserved();
        }
        $this->load_routine_body($func);
    }
    
    function load_routine_body($routine) {
        // [characteristic ...] BEGIN routine_body END
        // characteristic:
        //   | [NOT] DETERMINISTIC
        //   | { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA }
        while ( $this->peek_next()->token() != 'BEGIN' and 
                $this->peek_next()->token() != 'RETURN' and
                $this->peek_next()->token() != 'CALL' ) {
            switch ($this->get_reserved()) {
                case 'CONTAINS SQL':
                case 'NO SQL':
                case 'READS SQL DATA':
                case 'MODIFIES SQL DATA':
                    $routine->access = $this->cur()->token();
                    break;
                case 'CONTAINS TRANSACTIONS':
                    $routine->txns = Modyllic_Routine::TXNS_HAS;
                    break;
                case 'CALL IN TRANSACTION':
                    $routine->txns = Modyllic_Routine::TXNS_CALL;
                    break;
                case 'NO TRANSACTIONS':
                    $routine->txns = Modyllic_Routine::TXNS_NONE;
                    break;
                case 'NOT DETERMINISTIC':
                    $routine->deterministic = FALSE;
                    break;
                case 'DETERMINISTIC':
                    $routine->deterministic = TRUE;
                    break;
                case 'COMMENT':
                    $this->get_string();
                    break;
                case 'BEGIN';
                    break;
                case 'RETURN';
                    break;
                case 'CALL';
                    break;
                default:
                    throw $this->error("Unknown characteristic in routine declaration: ".$this->cur()->debug());
            }
        }
        if ( $this->peek_next()->token() == 'BEGIN' ) {
            $routine->body = trim($this->rest());
        }
        else {
            $routine->body = "BEGIN\n    " . trim($this->rest()) . "\nEND";
        }
    }
    
    private $column_term = array( ',', ')' );

    function get_args() {
        // [ IN | OUT | INOUT ] param_name type comment
        // Leading comments refer to the preceding comment, trailing comments
        // refer to the argument they follow.
        $this->get_symbol('(');
        $args = array();
        $this->next();
        $lastarg = null;
        while ( $this->cur()->value() != ')' ) {
            if ( $this->cur() instanceOf Modyllic_Token_EOC ) {
                throw $this->error("Command ended while looking for close of argument list");
            }
            $arg = new Modyllic_Arg();
            while ( $this->cur() instanceOf Modyllic_Token_Comment ) {
                if ( isset($lastarg) ) {
                    $lastarg->docs = trim( $lastarg->docs . " " . $this->cur()->value() );
                }
                $this->next();
            }
            $arg->dir = 'IN';
            if ( $this->cur() instanceOf Modyllic_Token_Reserved and
                 in_array($this->cur()->token(), array('IN','INOUT','OUT')) ) {
                $arg->dir = $this->cur()->token();
                $this->next();
            }
            $arg->name = $this->assert_ident();
            $arg->type = $this->get_type();
            while ( $this->next() instanceOf Modyllic_Token_Comment ) {
                $arg->docs = trim( $arg->docs . " " . $this->cur()->value() );
            }
            $args[] = $arg;
            $this->assert_symbol( $this->column_term );
            if ( $this->cur()->value() == ',' ) {
                $this->next();
            }
        }
        while ( $this->peek_next() instanceOf Modyllic_Token_Comment ) {
            $this->next();
            if ( isset($lastarg) ) {
                $lastarg->docs = trim( $lastarg->docs . " " . $this->cur()->value() );
            }
        }
        return $args;
    }
    
    function get_type() {
        // reserved[(token ...)>] [SIGNED|UNSIGNED] [ZEROFILL] [BINARY|ASCII|UNICODE] [{CHARACTER SET|CHARSET} ident] [COLLATE ident]
        $type = Modyllic_Type::create( $this->get_reserved() );
        if ( $this->peek_next()->value() == '(' ) {
            $this->get_symbol();
            if ( $type instanceOf Modyllic_Numeric ) {
                $args = $this->get_array();
                $type->length = $args[0];
                if ( count($args) > 1 ) {
                    $type->scale = $args[1];
                }
            }
            else if ( $type instanceOf Modyllic_Float ) {
                $args = $this->get_array();
                $type->length = $args[0];
                if ( count($args) > 1 ) {
                    $type->decimals = $args[1];
                }
            }
            else if ( $type instanceOf Modyllic_Compound ) {
                $type->values = $this->get_array();
            }
            else {
                $type->length = $this->get_list();
                if ( $type instanceOf Modyllic_VarChar and $type->length > 65535 ) {
                    $type = new Modyllic_Text($type->name,$type->length);
                }
                else if ( $type instanceOf Modyllic_VarBinary and $type->length > 65535 ) {
                    $type = new Modyllic_Blob($type->name,$type->length);
                }
            }
        }
        $binary = FALSE;
        while ( $this->peek_next() instanceOf Modyllic_Token_Reserved ) {
            if ( in_array( $this->peek_next()->token(), array( 'SIGNED', 'UNSIGNED', 'ZEROFILL', 'ASCII', 'UNICODE', 'BINARY' ) ) ) {
                switch ( $this->get_reserved() ) {
                    case 'SIGNED':   $type->unsigned = FALSE; break;
                    case 'UNSIGNED': $type->unsigned = TRUE; break;
                    case 'ZEROFILL': $type->zerofill = TRUE; $type->unsigned = TRUE; break;
                    case 'ASCII':    $type->charset('latin1'); $type->collate('latin1_general_ci'); break;
                    case 'UNICODE':  $type->charset('ucs2'); $type->collate('ucs2_general_ci'); break;
                    case 'BINARY':   $binary = TRUE; break;
                }
            }
            else if ( in_array( $this->peek_next()->token(), array('CHARACTER SET', 'CHARSET') ) ) {
                $this->get_reserved();
                $new = $this->get_ident();
                if ( isset($type->charset) and isset($type->collate) ) {
                    $type->collate = $this->updated_collate( $type->charset, $new, $type->collate );
                }
                $type->charset( $new );
            }
            else if ( $this->peek_next()->token() == 'COLLATE' ) {
                $this->get_reserved();
                $type->collate( $this->get_ident() );
            }
            else {
                break;
            }
        }
        
        if ( ( $type instanceOf Modyllic_VarChar or $type instanceOf Modyllic_Text ) and strtolower($type->charset()) == 'binary' ) {
            $type = $type->binary();
        }
        else if ( $binary ) {
            $type->collate( $type->charset() . "_bin" );
        }
        
        if ( ! $type->isValid() ) {
#            throw $this->error( "Syntax error in type declaration of ".$type->toSql() );
        }
        
        return $type;
    }

    function cmd_CREATE_TABLE() {
        // CREATE TABLE ident ( create_definition,... ) table_option...
        // table_option:
        //     [ENGINE=<IDENT>] 
        //   | [[DEFAULT] {CHARACTER SET|CHARSET}=ident] 
        //   | [[DEFAULT] COLLATE=ident] 
        //   | [AUTO_INCREMENT=number]
        //   | [COMMENT=string]
        $table = $this->get_ident();
        if ( isset($this->schema->views[$table]) ) {
            throw $this->error("Can't create TABLE $table when a view of that name already exists");
        }
        $this->get_symbol('(');

        $this->ctx = $this->schema->add_table( new Modyllic_Table($table) );
        $this->ctx->charset = $this->schema->charset;
        $this->ctx->collate = $this->schema->collate;
        $this->ctx->docs = $this->cmddocs;
        
        // Load tablespec
        while (! $this->next() instanceOf Modyllic_Token_EOC ) {

            # A key or column spec, followed by...
            if ( $this->cur() instanceOf Modyllic_Token_Reserved ) {
                $this->load_key();
            }
            else {
                $this->load_column();
            }
            
            # Comments
            while ( $this->cur() instanceOf Modyllic_Token_Comment ) {
                $this->ctx->last_index->docs = trim( $this->ctx->last_index->docs . " " . $this->cur()->value() );
                $this->next();
            }
            
            # end of keys and columns
            if ( $this->cur()->value() == ')' ) {
                $this->assert_symbol();
                break;
            }
            # or a comma
            else if ( $this->cur()->value() == ',' ) {
                $this->assert_symbol();
                # and some number of additional comments
                while ( $this->peek_next() instanceOf Modyllic_Token_Comment ) {
                    $this->ctx->last_index->docs = trim( $this->ctx->last_index->docs . " " . $this->next()->value() );
                }
            }
            else {
                throw $this->error("Unknown token between columns ".$this->cur()->debug().", expected ',' or ')'.");
            }
        }
        
        // Load table flags
        while ( ! $this->peek_next() instanceOf Modyllic_Token_EOC ) {
            $this->next();
            if ( $this->maybe_table_option() ) { }
            else {
                throw $this->error("Unknown table flag ".$this->cur()->debug().", expected ENGINE, CHARSET or COLLATE");
            }
        }
        foreach ($this->ctx->columns as &$col) {
            if ( $col->type instanceOf Modyllic_String ) {
                $col->type->set_default_charset( $this->ctx->charset );
                $col->type->set_default_collate( $this->ctx->collate );
            }
        }
        foreach ($this->ctx->indexes as &$index) {
            if ($index instanceOf Modyllic_Index_Foreign ) {
                $this->add_foreign_key_index( '', $index );
            }
        }
        $this->ctx = null;
    }
    
    function maybe_table_option() {
        if ( $this->cur()->token() == 'DEFAULT' ) {
            $this->get_reserved(array( 'CHARSET', 'CHARACTER SET', 'COLLATE' ));
        }
        if ( $this->cur()->token() == 'ENGINE' ) {
            $this->maybe( '=' );
            $this->get_reserved();
            $this->ctx->engine = $this->cur()->value(); # We want the user's capitalization
        }
        else if ( $this->cur()->token() == 'CHARSET' or $this->cur()->token() == 'CHARACTER SET' ) {
            $this->maybe( '=' );
            $new = $this->get_ident();
            $this->ctx->collate = $this->updated_collate( $this->ctx->charset, $new, $this->ctx->collate );
            $this->ctx->charset = $new;
        }
        else if ( $this->cur()->token() == 'COLLATE' ) {
            $this->maybe( '=' );
            $this->ctx->collate = $this->get_ident();
        }
        else if ( $this->cur()->token() == 'AUTO_INCREMENT' ) {
            $this->maybe( '=' );
            //// We just ignore the auto_increment number (for now anyway)
            $this->get_num();
        }
        else if ( $this->cur()->token() == 'COMMENT' ) {
            $this->maybe( '=' );
            $this->get_string();
        }
        else if ( $this->cur()->token() == 'PACK_KEYS' ) {
            $this->maybe( '=' );
            $this->get_num();
        }
        else if ( $this->cur()->token() == 'MAX_ROWS' ) {
            $this->maybe( '=' );
            $this->get_num();
        }
        else if ( $this->cur()->token() == 'AVG_ROW_LENGTH' ) {
            $this->maybe( '=' );
            $this->get_num();
        }
        else {
            return false;
        }
        return true;
    }
    
    function load_column() {
        // ident type [NOT NULL|NULL] [DEFAULT value] [ON UPDATE token] [AUTO_INCREMENT]
        //   [PRIMARY KEY] [COMMENT string] [ALIASES (token,...)]
        $column = $this->ctx->add_column(new Modyllic_Column( $this->assert_ident() ));
        $column->type = $this->get_type();
        
        $is_unique = FALSE;
        
        if ( $column->type->name == 'SERIAL' ) {
            // SERIAL is an alias for BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE.
            $column->type = new Modyllic_BigInt('BIGINT');
            $column->type->unsigned = TRUE;
            $column->null = FALSE;
            $column->auto_increment = TRUE;
            $column->default = null; # No default
            $is_unique = TRUE;
        }
        
        if ( $column->type instanceOf Modyllic_Timestamp ) {
            $column->default = 'CURRENT_TIMESTAMP';
            $column->on_update = 'CURRENT_TIMESTAMP';
        }

        $is_primary = FALSE;
        while ( ! in_array($this->next()->value(), $this->column_term) ) {
            if ( $this->cur() instanceOf Modyllic_Token_Comment ) {
                $column->docs .= trim( $column->docs . ' ' . $this->cur()->value() );
                continue;
            }
            $this->assert_reserved();
            if ( $this->cur()->token() == 'NOT NULL' ) {
                $column->null = FALSE;
                // If the default was set to NULL either implicitly or explicitly but the column
                // is not nullable then we clear the default.
                if ( $column->default == 'NULL' ) {
                    $column->default = null;
                }
            }
            else if ( $this->cur()->token() == 'NULL' ) {
                if ( ! $column->null ) {
                    throw $this->error("Can't set column to NULL after setting it to NOT NULL");
                }
                $column->null = TRUE;
            }
            else if ( $this->cur()->token() == 'DEFAULT' ) {
                $column->default = $column->type->normalize( $this->next() );
                if ( $this->peek_next()->token() == 'ON UPDATE' ) {
                    $this->get_reserved();
                    $column->on_update = $this->get_reserved();
                }
            }
            else if ( $this->cur()->token() == 'PRIMARY KEY' ) {
                $is_primary = true;
            }
            else if ( $this->cur()->token() == 'UNIQUE' ) {
                if ( $this->peek_next()->token() == 'KEY' ) {
                    $this->get_reserved();
                }
                $is_unique = true;
            }
            else if ( $this->cur()->token() == 'AUTO_INCREMENT' ) {
                $column->auto_increment = TRUE;
            }
            else if ( $this->cur()->token() == 'ALIASES' ) {
                $this->get_symbol('(');
                $column->aliases += $this->get_array();
            }
            else if ( $this->cur()->token() == 'REFERENCES' ) {
                $key = new Modyllic_Index_Foreign();
                if ( $this->peek_next()->token() == 'WEAKLY' ) {
                    $this->get_reserved();
                    $key->weak = TRUE;
                }
                $key->columns = array( $column->name => FALSE );
                $key->references['table'] = $this->get_ident();
                $this->get_symbol('(');
                $key->references['columns'] = $this->get_array();

                while ( ! in_array( $this->peek_next()->value(), $this->column_term ) ) {
                    if ( $this->peek_next()->token() == 'ON DELETE' ) {
                        $this->get_reserved();
                        $key->references['on_delete'] = $this->get_reserved();
                    }
                    else if ( $this->peek_next()->token() == 'ON UPDATE' ) {
                        $this->get_reserved();
                        $key->reference['on_update'] = $this->get_reserved();
                    }
                    else if ( $this->peek_next() instanceOf Modyllic_Token_Comment ) {
                        $column->docs .= trim( $column->docs . ' ' . $this->next()->value() );
                    }
                    else {
                        break;
                    }
                }
                $key->cname = $this->gen_constraint_name($key);
                $this->add_index( $key );
            }
            else if ( $this->cur()->token() == 'COMMENT' ) {
                $column->docs .= trim( $column->docs . ' ' . $this->get_string() );
            }
            else {
                throw $this->error("Unknown token in column declaration: ".$this->cur()->debug());
            }
        }
        if ( $is_primary ) {
            $index = new Modyllic_Index('!PRIMARY KEY');
            $index->primary = TRUE;
            $index->columns = array($column->name => FALSE);
            $this->add_index( $index );
        }
        else if ( $is_unique ) {
            $index = new Modyllic_Index($column->name);
            $index->unique = TRUE;
            $index->columns = array($column->name => FALSE);
            $this->add_index( $index );
        }
    }

    function load_key() {
        //     CONSTRAINT [ident] FOREIGN KEY [ident] (ident,...)
        //          REFERENCES ident (ident,...) [ON DELETE reserved] [ON UPDATE reserved]
        //   | PRIMARY KEY (ident,...) [USING {BTREE|HASH}]
        //   | [UNIQUE|FULLTEXT] KEY ident (ident,...) [USING {BTREE|HASH}]
        $token = $this->assert_reserved();
        if ( $token == 'CONSTRAINT' or $token == 'FOREIGN KEY' ) {
            $key = new Modyllic_Index_Foreign();
            if ( $token == 'CONSTRAINT' ) {
                $key->cname = $this->get_ident();
                $token = $this->get_reserved();
            }
            $key->foreign = TRUE;

            // The name of the regular index part, optional, before the column list
            $name = '';
            if ( $this->peek_next()->value() != '(' ) {
                $name = $this->get_ident();
            }
            
            // If you set a constraint name it overrides any regular index
            // part you specified.
            if ( $key->cname ) {
                $name = $key->cname;
            }
            
            // after the name, the column list is mandetory
            $this->get_symbol('(');
            $key->columns = $this->index_columns();

            $this->get_reserved( 'REFERENCES' );
            if ( $this->peek_next()->token() == 'WEAKLY' ) {
                $this->get_reserved();
                $key->weak = TRUE;
            }
            $key->references['table'] = $this->get_ident();
            
            $this->get_symbol('(');
            $key->references['columns'] = $this->get_array();
            
            while ( ! in_array( $this->next()->value(), $this->column_term ) ) {
                if ( $this->cur()->token() == 'ON DELETE' ) {
                    $key->references['on_delete'] = $this->get_reserved();
                }
                else if ( $this->cur()->token() == 'ON UPDATE' ) {
                    $key->reference['on_update'] = $this->get_reserved();
                }
                else if ( $this->cur() instanceOf Modyllic_Token_Comment ) {
                    $key->docs .= trim( $key->docs . ' ' . $this->cur()->value() );
                }
                else {
                    throw $this->error( "Error in foreign key declaration in ".$this->ctx->name.", expecting one of ".
                        "'".implode("', '", array( 'ON DELETE', 'ON UPDATE' ) + $this->column_term )."' got ".$this->cur()->debug() );
                }
            }
            $this->assert_symbol();
            if ( ! $key->cname ) {
                $key->cname = $this->gen_constraint_name($key);
            }
        }
        else {
            $key = new Modyllic_Index();
            while ( 1 ) {
                $this->assert_reserved();
                if ( $token == 'PRIMARY KEY' ) {
                    $key->primary = TRUE;
                    $key->name = '!PRIMARY KEY';
                    break;
                }
                else if ( $token == 'UNIQUE' ) {
                    $key->unique = TRUE;
                }
                else if ( $token == 'FULLTEXT' ) {
                    $key->fulltext = TRUE;
                }
                else if ( $token == 'SPATIAL' ) {
                    $key->spatial = TRUE;
                }
                else if ( $token == 'KEY' or $token == 'INDEX' ) {
                    break;
                }
                else {
                    throw $this->error( "Error in index declaration, expected PRIMARY KEY, UNIQUE, FULLTEXT or KEY, got ".$this->cur()->debug() );
                }
                $token = $this->next()->token();
            }
            if ( $this->peek_next()->value() != '(' ) {
                $key->name = $this->get_ident();
            }
            $this->get_symbol('(');
            $key->columns = $this->index_columns();

            if ( ! $key->name ) {
                $key->name = $this->gen_index_name($key);
            }
            
            if ( $this->peek_next()->token() == 'USING' ) {
                $this->get_reserved();
                $key->using = $this->get_reserved(array('BTREE','HASH'));
            }
            $this->next();
        }
        $this->add_index( $key );
    }
    
    function index_columns() {
        $columns = array();
        $last_col = null;
        while ( $this->next()->value() != ')' ) {
            if ( $this->cur() instanceOf Modyllic_Token_EOC ) {
                throw $this->error( "Hit end of command while looking for $end" );
            }
            if ( $this->cur()->value() != ',' ) {
                $colname = $this->cur()->value();
                if ( $this->maybe('(') ) {
                    $columns[$last_col] = $this->get_num();
                    $this->get_symbol(')');
                }
                else {
                    $columns[$colname] = FALSE;
                }
            }
        }
        return $columns;
    }
    
    function add_index($key) {
        // If a semantically identical key already exists, replace it.
        $match = null;
        foreach ($this->ctx->indexes as $array_index=>&$index) {
            if ( $key->equalTo($index) ) {
                $match = $array_index;
                break;
            }
        }
        if ( isset($match) ) {
            unset($this->ctx->indexes[$match]);
        }
        $this->ctx->add_index( $key );
    }
    
    function gen_constraint_name($key) {
        return $this->ctx->gen_index_name( $this->ctx->name . "_ibfk", TRUE );
    }
    function gen_index_name($key) {
        return $this->ctx->gen_index_name( $key->columns[0] );
    }

    function add_foreign_key_index( $name, $key ) {
        // If a name was specified and it already exists as an index, then that
        // index must be compatible
        if ( $name and isset( $this->ctx->indexes[$name] ) ) {
            $regkey = $this->ctx->indexes[$name];
            $error = $this->error("In table ".$this->ctx->name.", a constraint ".$name." could not be added due to a duplicate key");
            if ( count($key->columns) > count($regkey->columns) ) {
                throw $error;
            }
            foreach ( $key->columns as $idx=>&$name ) {
                if ( $regkey->columns[$idx] != $key->columns[$idx] ) {
                    throw $error;
                }
            }
        }
        else {
            // Scan to see if another key would meet our needs
            $matched = FALSE;
            foreach ( $this->ctx->indexes as &$otherKey ) {
                if ( $otherKey instanceOf Modyllic_Index_Foreign ) { continue; }
                if ( count($key->columns) <= count($otherKey->columns) ) {
                    $matched = TRUE;
                    foreach ( $key->columns as $idx=>&$colname ) {
                        if ( !isset($otherKey->columns[$idx]) or $colname != $otherKey->columns[$idx] ) {
                            $matched = FALSE;
                            break;
                        }
                    }
                    if ($matched) { break; }
                }
            }
            if ( ! $name ) {
                $first = array_shift( array_keys($key->columns) );
                $name = $this->ctx->gen_index_name($first);
            }
            if ( ! $matched ) {
                $regkey = new Modyllic_Index($name);
                $regkey->columns = $key->columns;
                $this->add_index( $regkey );
            }
        }
    }
    
    /**
     * If the next token has a value of $thing then advance to it and return
     * true, else return false.
     * @param str $thing
     * @returns bool
     */
    function maybe($thing) {
        if ( ! is_array($thing) ) {
            $thing = array($thing);
        }
        if ( in_array( $this->peek_next()->token(), $thing ) ) {
            $this->next();
            return TRUE;
        }
        else {
            return FALSE;
        }
    }
    
    /**
     * Fetch the next token and ensure that it's an identifier
     * @returns the value of the identifier
     */
    function get_ident() {
        $this->next();
        return $this->assert_ident();
    }

    /**
     * Assert that the current token is an identifier
     * @returns the value of the identifier
     */
    function assert_ident() {
        if ( ! $this->cur() instanceOf Modyllic_Token_Ident ) {
            throw $this->error( "Expected identifier, got ".$this->cur()->debug() );
        }
        return $this->cur()->value();
    }

    /**
     * Fetch the next token and ensure that it's a number
     * @returns the number
     */
    function get_num() {
        $this->next();
        return $this->assert_num();
    }

    /**
     * Assert that the current token is a number
     * @returns the number
     */
    function assert_num() {
        if ( ! $this->cur() instanceOf Modyllic_Token_Num ) {
            throw $this->error( "Expected number, got ".$this->cur()->debug() );
        }
        return $this->cur()->value();
    }

    /**
     * Fetch the next token and ensure that it's a symbol (and that it's one
     * of $valid_symbols)
     * @param string|array $valid_symbols 
     * @returns the symbol
     */
    function get_symbol($valid_symbols = null) {
        $this->next();
        return $this->assert_symbol($valid_symbols);
    }

    /**
     * Assert that the current token is a a symbol (and that it's one of
     * $valid_symbols)
     * @param string|array $valid_symbols 
     * @returns the symbol
     */
    function assert_symbol($valid_symbols = null) {
        if ( ! $this->cur() instanceOf Modyllic_Token_Symbol ) {
            throw $this->error( "Expected SYMBOL, got ".$this->cur()->debug() );
        }
        
        if ( is_null($valid_symbols) ) {  return $this->cur()->value(); }

        if ( ! is_array($valid_symbols) ) { $valid_symbols = array($valid_symbols); }
        
        if ( ! in_array( $this->cur()->value(), $valid_symbols ) ) {
            throw $this->error( "Expected one of '".implode("', '",$valid_symbols)."', got ".$this->cur()->debug() );
        }
        return $this->cur()->value();
    }
    
    /*
     * Fetch the next token and ensure that it's a string
     * @returns the string
     */
    function get_string() {
        $this->next();
        return $this->assert_string();
    }

    /*
     * Assert that the current token is a string
     * @returns the string
     */
    function assert_string() {
        if ( ! $this->cur() instanceOf Modyllic_Token_String ) {
            throw $this->error( "Expected string, got ".$this->cur()->debug() );
        }
        return $this->cur()->value();
    }

    /**
     * Fetch the next token and ensure that it's a reserved word
     * @returns the token form of the reserved word (all caps)
     */
    function get_reserved( $t1=null ) {
        $this->next();
        return $this->assert_reserved($t1);
    }

    /*
     * Assert that the current token is a reserved word
     * @returns the token form of the reserved word (all caps)
     */
    function assert_reserved( $t1=null ) {
        if ( ! $this->cur() instanceOf Modyllic_Token_Reserved ) {
            throw $this->error( "Expected reserved word, got ".$this->cur()->debug() );
        }
        if ( is_null($t1) ) { return $this->cur()->token(); }
        
        if ( ! is_array($t1) ) { $t1 = array($t1); }
        
        if ( ! in_array($this->cur()->token(),$t1) ) {
            throw $this->error( "Expected '".implode("', '",$t1)."', got ". $this->cur()->debug() );
        }
        return $this->cur()->token();
    }
    
    /**
     * Like get_array, but returns a comma separated list in a string rather
     * then an array.
     *
     * @param string $end (Default: ')')
     * @returns string
     */
    function get_list( $end=')' ) {
        $value = implode(",",$this->get_array($end));
        // We inject and then return next so that the this will become
        // the current token.  If we just returned the new token it wouldn't
        // do that.
        $this->tok->inject( new Modyllic_Token_List($this->tok->pos,$value) );
        return $this->next()->value();
    }
    
    function _value_map( $token ) {
        return $token->value();
    }
    
    /**
     * This is like get_token_array but returns the values of the tokens rather then the
     * tokens themselves.
     *
     * @param string $end (Default: ")")
     * @returns array of mixed
     */
    function get_array( $end=")" ) {
        $value = $this->get_token_array($end);
        $value = array_map( array($this,"_value_map"), $value );
        // We inject and then return next so that the this will become
        // the current token.  If we just returned the new token it wouldn't
        // do that.
        $this->tok->inject( new Modyllic_Token_List($this->tok->pos,$value) );
        return $this->next()->value();
    }

    /**
     * Takes all of the tokens up till the $end and returns them as a
     * Modyllic_Token_List.  However, unlike get_list, this returns an array
     * of the tokens found, ignoring whitespace and commas.  Note that
     * multiple commas will not result in a blank entry.  They will be
     * treated like a single comma.
     *
     * @param string $end (Default: ")")
     * @returns array of Modyllic_Token
     */
    function get_token_array( $end=")" ) {
        $value = array();
        while ( $this->next()->value() != $end ) {
            if ( $this->cur() instanceOf Modyllic_Token_EOC ) {
                throw $this->error( "Hit end of command while looking for $end" );
            }
            if ( ! $this->cur() instanceOf Modyllic_Token_Symbol or 
                 $this->cur()->value() != "," ) {
                $value[] = $this->cur();
            }
        }
        // We inject and then return next so that the this will become
        // the current token.  If we just returned the new token it wouldn't
        // do that.
        $this->tok->inject( new Modyllic_Token_List($this->tok->pos,$value) );
        return $this->next()->value();
    }
    
    /**
     * Throw an exception with information about where in the parse it failed.
     */
    function error($token,$message=null) {
        if ( !isset($message) ) {
            $message = $token;
            $token = $this->cur();
        }
        $line = $this->tok->line();
        $col  = $this->tok->col();
        return new Modyllic_Exception( $this->filename, $line + 1, $col + 1, $this->tok->context(), $message );
    }
}

/**
 * Our exception class, it takes a bunch of useful debugging information
 */
class Modyllic_Exception extends Exception {
    /**
     * @param string $filename 
     * @param int $line
     * @param int $col
     * @param string $context
     * @param string $message
     */
    function __construct( $filename, $line, $col, $context, $message ) {
        parent::__construct("$message while parsing SQL in $filename on line $line at col $col:\n\n$context" );
    }
}
