<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator_PHP {
    protected $php;
    protected $indent_by = 4;
    protected $indent_with = " ";
    protected $level = 0;
    protected $in_cmd = 0;
    protected $filters = array();

    protected function indent() {
        $this->add( str_repeat( $this->indent_with, $this->indent_by * $this->level ) );
        return $this;
    }
    protected function cmd($str,$term="") {
        if ( $str != "" ) {
            $this->begin_cmd($str)
                 ->end_cmd("",$term);
        }
        return $this;
    }
    protected function begin_cmd($str="") {
        if ( ! $this->in_cmd ) {
            $this->indent();
        }
        $this->in_cmd ++;
        $this->add($str);
        return $this;
    }
    protected function end_cmd($str=null, $term="") {
        $this->in_cmd --;
        if ( ! is_null($str) ) {
            $this->add($str);
            if ( ! $this->in_cmd ) {
                $this->add( "$term\n" );
            }
        }
        return $this;
    }
    protected function add($str) {
        foreach ($this->filters as $filter) {
            $str = call_user_func($filter,$str);
        }
        $this->php .= $str;
        return $this;
    }
    protected function push_filter($filter) {
        array_unshift($this->filters,$filter);
        return $this;
    }
    protected function pop_filter() {
        array_shift($this->filters);
        return $this;
    }

    protected function begin_block($str="",$term="") {
        $this->cmd( $str, $term );
        $this->level ++;
        return $this;
    }
    protected function end_block($str="", $term="") {
        $this->level --;
        $this->cmd($str,$term);
        return $this;
    }

    protected function begin_group() {
        $this->begin_cmd('(');
        return $this;
    }
    protected function end_group() {
        $this->end_cmd(')',';');
        return $this;
    }

    protected function add_str($str) {
        $this->begin_str()
               ->add($str)
             ->end_str();
        return $this;
    }

    protected function begin_str() {
        $this->add("'");
        $this->push_filter( "addslashes" );
        return $this;
    }

    protected function end_str() {
        $this->pop_filter();
        $this->add("'");
        return $this;
    }

    protected function add_dstr($str) {
        $this->begin_dstr()
               ->add($str)
             ->end_dstr();
        return $this;
    }

    protected function begin_dstr() {
        $this->add('"');
        $this->push_filter( "addslashes" );
        return $this;
    }

    protected function end_dstr() {
        $this->pop_filter();
        $this->add('"');
        return $this;
    }

    protected function add_assert($cmd) {
        $this->begin_assert()
               ->add($cmd)
             ->end_assert();
        return $this;
    }
    protected function begin_assert() {
        $this->begin_cmd("assert(");
        $this->begin_str();
        return $this;
    }
    protected function end_assert() {
        $this->end_str()
             ->end_cmd(")",";");
        return $this;
    }

    # These are a bit magic, due to needing to end the string as part of the
    # closing block
    protected function begin_assert_block() {
        $this->begin_block("assert('");
        $this->push_filter( "addslashes" );
        return $this;
    }
    protected function end_assert_block() {
        $this->pop_filter();
        $this->end_block("')",";");
        return $this;
    }
    protected function add_var($var) {
        $var = preg_replace("/\W/","_",$var);
        $this->cmd( '$' . $var, ';' );
        return $this;
    }
    protected function begin_assign($var,$index=null) {
        $this->begin_cmd();
        $this->add_var($var);
        if ( isset($index) ) {
            $this->add($index);
        }
        $this->op('=');
        return $this;
    }
    protected function begin_list_assign($var) {
        $this->begin_cmd();
        $this->func_var('list',$var);
        $this->op('=');
        return $this;
    }
    protected function end_assign() {
        $this->end_cmd( '', ';' );
        return $this;
    }
    protected function assign($var,$value) {
        $this->begin_assign( $var );
        $this->add($value);
        $this->end_assign();
        return $this;
    }
    protected function add_break() {
        $this->cmd('break', ';' );
        return $this;
    }
    protected function begin_foreach( $array, $key, $value ) {
        $this->begin_cmd( 'foreach (' )
               ->add_var( $array )
               ->add( ' as ' )
               ->add_var( $key )
               ->add( '=>' )
               ->add_var( $value )
             ->end_cmd( ') {' );
        $this->begin_block();
        return $this;
    }
    protected function end_foreach() {
        $this->end_block('}');
        return $this;
    }
    protected function begin_try() {
        $this->cmd('try {')
             ->begin_block();
        return $this;
    }
    protected function class_name($class) {
        $this->add($class);
        return $this;
    }
    protected function and_catch($class="Exception") {
        $this->end_block('}')
             ->begin_cmd('catch (')
               ->class_name($class)
               ->add(' ')
               ->add_var('e')
             ->end_cmd(') {')
             ->begin_block();
        return $this;
    }
    protected function end_try() {
        $this->end_block('}');
        return $this;
    }
    protected function new_obj($class,array $args=array()) {
        $this->begin_new($class)
               ->add_args($args)
             ->end_new();
        return $this;
    }
    protected function begin_new($class) {
        $this->begin_cmd("new ")
             ->class_name($class)
             ->add('(');
        return $this;
    }
    protected function end_new() {
        $this->end_cmd(')',';');
        return $this;
    }
    protected function throw_new($class,array $args=array()) {
        $this->begin_throw()
               ->new_obj($class,$args)
             ->end_throw();
        return $this;
    }
    protected function begin_throw() {
        $this->begin_cmd('throw ');
        return $this;
    }
    protected function end_throw() {
        $this->end_cmd('',';');
        return $this;
    }
    protected function begin_while_expr() {
        $this->begin_cmd('while (');
        return $this;
    }
    protected function end_while_expr() {
        $this->end_cmd(') {');
        $this->begin_block();
        return $this;
    }
    protected function end_while() {
        $this->end_block('}');
        return $this;
    }
    protected function begin_if_expr() {
        $this->begin_cmd('if (');
        return $this;
    }
    protected function end_if_expr() {
        $this->end_cmd(') {');
        $this->begin_block();
        return $this;
    }
    protected function begin_if($value) {
        $this->begin_if_expr()
               ->add( $value )
             ->end_if_expr();
        return $this;
    }
    protected function begin_else() {
        $this->end_if()
             ->cmd('else {')
             ->begin_block();
        return $this;
    }
    protected function end_if() {
        $this->end_block('}');
        return $this;
    }
    protected function begin_in_array( $var = null) {
        $this->begin_cmd('in_array(');
        if ( isset($var) ) {
            $this->add_var($var);
            $this->sep();
        }
        return $this;
    }
    protected function end_in_array() {
        $this->end_cmd(')',';');
        return $this;
    }
    function method($obj,$name=null,$args=null) {
        if ( is_null($name) or is_array($name)) {
            $args = $name;
            $name = $obj;
            $obj = null;
        }
        $this->begin_method($obj,$name);
        if ( isset($args) ) {
            $this->func_args($args);
        }
        $this->end_method();
        return $this;
    }
    function begin_method($obj,$name=null) {
        if ( is_null($name) ) {
            $name = $obj;
            $obj = null;
        }
        $this->begin_cmd();
        if ( isset($obj) ) {
            $this->add_var($obj);
        }
        $this->add("->$name(");
        return $this;
    }
    function end_method() {
        $this->add(")");
        $this->end_cmd('', ';');
        return $this;
    }
    function func_var($func,$var) {
        $this->begin_cmd($func.'(')
               ->func_args($var)
             ->end_cmd(')',';');
        return $this;
    }
    function func_args($args) {
        if ( !is_array($args) ) {
            $args = array($args);
        }
        $argc = 0;
        foreach ( $args as $arg ) {
            if ( $argc++ ) {
                $this->sep();
            }
            $this->add_var($arg);
        }
        return $this;
    }
    function op($op) {
        $this->add(" $op ");
        return $this;
    }
    function op_var($var,$op,$value) {
        $this->begin_op_var($var,$op)
               ->add($value)
             ->end_op_var();
        return $this;
    }
    function begin_op_var($var,$op) {
        $this->begin_cmd()
               ->add_var($var)
             ->op($op);
        return $this;
    }
    function end_op_var() {
        $this->end_cmd( '', ';' );
        return $this;
    }
    function preg_match( $regexp, $var ) {
        $this->begin_cmd( 'preg_match(' )
               ->add_str( $regexp )
               ->sep()
               ->add_var($var)
             ->end_cmd(')',';');
        return $this;
    }
    function add_array( $values=null ) {
        $this->begin_array();
        if ( isset($values) ) {
            if ( is_array($values) ) {
                $valuec = 0;
                foreach ($values as $value ) {
                    if ( $valuec++ ) {
                        $this->sep();
                    }
                    $this->add( $value );
                }
            }
            else {
                $this->add( $values );
            }
        }
        $this->end_array();
        return $this;
    }
    function begin_array() {
        $this->begin_cmd('array(');
        return $this;
    }
    function end_array() {
        $this->end_cmd(')',';');
        return $this;
    }
    function index( $var, $value ) {
        $this->begin_index($var)
               ->add_str($value)
             ->end_index();
        return $this;
    }
    function begin_index( $var ) {
        $this->begin_cmd();
        $this->add_var($var);
        $this->add("[");
        return $this;
    }
    function end_index() {
        $this->end_cmd("]",";");
        return $this;
    }
    function begin_return() {
        $this->begin_cmd('return ');
        return $this;
    }
    function end_return() {
        $this->end_cmd('',';');
        return $this;
    }
    function add_return($var=null) {
        $this->begin_return();
        if ( isset($var) ) {
            $this->add_var($var);
        }
        $this->end_return();
        return $this;
    }
    function add_false() {
        $this->add('false');
        return $this;
    }
    function add_true() {
        $this->add('true');
        return $this;
    }

    function sep() {
        $this->add(', ');
        return $this;
    }
    function add_const($const) {
        $this->add($const);
        return $this;
    }
    function begin_sql_cmd($cmd) {
        $this->add("$cmd ");
        return $this;
    }
    function end_sql_cmd() { }
    function begin_sql_func($name) {
        $this->add( Modyllic_SQL::quote_ident($name) . '(' );
        return $this;
    }
    function end_sql_func() {
        $this->add( ')' );
        return $this;
    }
    function start_phpdoc() {
        $this->indent();
        $this->add("/**\n");
        return $this;
    }
    function phpdoc($line) {
        $this->indent();
        $this->add(" * $line\n");
        return $this;
    }
    function end_phpdoc() {
        $this->indent();
        $this->add(" */\n");
        return $this;
    }
    function ref() {
        $this->add('&');
        return $this;
    }
    function add_not() {
        $this->add('!');
        return $this;
    }
    function routine_sep() {
        $this->add("\n");
        return $this;
    }
    function suppress_warnings() {
        $this->add('@');
        return $this;
    }

    /**
     * Generate PHP helper methods from a schema
     *
     * @param Modyllic_Schema $schema
     * @param string $class
     * @returns string
     */
    function helpers($schema, $class) {
        $this->preamble( $class );
        $this->begin_class( $class );

        foreach ( $schema->routines as $name=>$routine ) {
            if ( $name[0] == "_" ) {
                // Names that start with _ are used only by other routines and are not exported
                continue;
            }

            $this->routine_helper($routine);
            $this->routine_sep();
        }
        $this->end_class( $class );
        return $this->get_and_flush_php();
    }
    function get_and_flush_php() {
        $php = $this->php;
        $this->php = "";
        return $php;
    }
    function preamble( $class ) {
        $this->cmd('<?php');
        return $this;
    }
    function begin_class($class) {
        $this->begin_block('class '.$class.' {');
        return $this;
    }
    function end_class($class) {
        $this->end_block("}");
        return $this;
    }
    function routine_helper(Modyllic_Schema_Routine $routine) {
        $this->docs($routine);
        $this->begin_func($routine)
               ->args_init($routine)
               ->args_validate($routine)
               ->call($routine)
             ->end_func();
        return $this;
    }
    function begin_func(Modyllic_Schema_Routine $routine) {
        $this->begin_cmd( 'public static function '.$routine->name.'(' )
               ->args($routine)
             ->end_cmd(') {');
        $this->begin_block();
        return $this;
    }
    function end_func() {
        $this->end_block('}');
        return $this;
    }
    function begin_txns(Modyllic_Schema_Routine $routine) {
        if ( $routine->txns == Modyllic_Schema_Routine::TXNS_HAS ) {
            $this->begin_cmd( 'if ( ')
                   ->dbh()
                   ->method( 'inTransaction' )
                 ->end_cmd(' ) {')
                 ->begin_block()
                   ->begin_throw()
                     ->begin_new('PDOException')
                       ->str('Stored procedure '.$routine->name.' CAN NOT be called with an active transaction')
                     ->end_new()
                   ->end_throw()
                 ->end_block();
        }
        else if ($routine->txns == Modyllic_Schema_Routine::TXNS_CALL ) {
            $this->begin_cmd( 'if ( ! ')
                   ->dbh()
                   ->method( 'inTransaction' )
                 ->end_cmd(' ) {')
                 ->begin_block()
                   ->begin_cmd()
                     ->dbh()
                     ->method( 'beginTransaction' )
                   ->end_cmd(';')
                   ->begin_assign( 'commitTransaction' )
                       ->add('true')
                   ->end_assign()
                 ->end_block('}');

        }
    }
    function end_txns(Modyllic_Schema_Routine $routine) {
        if ($routine->txns == Modyllic_Schema_Routine::TXNS_CALL ) {
            $this->begin_cmd( 'if ( ')
                   ->func_var( 'isset', 'commitTransaction' )
                 ->end_cmd(' ) {')
                 ->begin_block()
                   ->begin_cmd()
                     ->dbh()
                     ->method( 'commit' )
                   ->end_cmd(';')
                 ->end_block('}');
        }
    }
    function call(Modyllic_Schema_Routine $routine) {
        $this->begin_txns($routine);

        $this->begin_assign('sth')
               ->dbh()
               ->begin_method( 'prepare' )
                 ->begin_str()
                   ->call_sql($routine)
                 ->end_str()
               ->end_method()
             ->end_assign();

        $this->bind_params($routine);
        $this->method('sth','execute');
        $this->returns($routine);
        return $this;
    }
    function dbh() {
        $this->add_var('dbh');
        return $this;
    }
    function docs(Modyllic_Schema_Routine $routine) {
        if ( $routine->docs != '' or count($routine->args) ) {
            $this->start_phpdoc();
            if ( $routine->docs ) {
                foreach ( explode("\n",$routine->docs) as $docline ) {
                    $this->phpdoc($docline);
                }
                $this->phpdoc('');
            }
            $this->args_docs($routine);
            $this->returns_docs($routine);
            $this->end_phpdoc();
        }
        return $this;
    }
    function args_docs(Modyllic_Schema_Routine $routine) {
        $this->args_docs_preamble($routine);
        switch ( $routine->args_type ) {
            case "LIST":
                $this->args_list_docs($routine->args);
                break;
            case "MAP":
                $this->args_map_docs($routine->args);
                break;
            default:
                throw new Exception("Unknown routine argument type: ".$routine->args_type);
        }
        return $this;
    }
    function args_docs_preamble(Modyllic_Schema_Routine $routine) {
        $this->phpdoc('@param $dbh');
        return $this;
    }
    function args(Modyllic_Schema_Routine $routine) {
        switch ( $routine->args_type ) {
            case "LIST":
                $this->args_list($routine->args);
                break;
            case "MAP":
                $this->args_map($routine->args);
                break;
            default:
                throw new Exception("Unknown routine argument type: ".$routine->args_type);
        }
        return $this;
    }
    function args_list_docs(array $args) {
        foreach ($args as $arg) {
            $this->phpdoc( '@param '.$arg->type->to_sql().' $'.$arg->name.' '.$arg->docs );
        }
        return $this;
    }
    function args_list(array $args) {
        $this->add_var('dbh');
        foreach ($args as $arg) {
            $this->sep();
            $this->arg($arg);
        }
        return $this;
    }
    function arg(Modyllic_Schema_Arg $arg) {
        if ( $arg->dir == "INOUT" or $arg->dir == "OUT" ) {
            $this->ref();
        }
        $this->add_var( $arg->name );
        return $this;
    }
    function args_map(array $args) {
        $this->add_var( 'dbh' );
        $this->sep();
        $this->add_var( 'args' );
        return $this;
    }
    function args_map_docs(array $args) {
        $this->phpdoc( '@param array $args Valid keys are:' );
        $max_len = 0;
        foreach ($args as $arg) {
            $max_len = max( $max_len, strlen($arg->name) );
        }
        foreach ($args as $arg) {
            $this->phpdoc( sprintf('       %-'.$max_len.'s => %s %s',$arg->name,$arg->type->to_sql(),$arg->docs ) );
        }
        return $this;
    }
    function args_init(Modyllic_Schema_Routine $routine) {
        if ( $routine->args_type == "MAP" ) {
            $this->args_map_init($routine);
        }
        return $this;
    }
    function args_validate(Modyllic_Schema_Routine $routine) {
        if ( $routine->args_type == "MAP" ) {
            $this->args_map_validate($routine);
        }
        $this->args_generic_validate($routine);
        return $this;
    }
    function args_generic_validate(Modyllic_Schema_Routine $routine) {
        foreach ($routine->args as $arg) {
            $this->arg_validate($arg);
        }
        return $this;
    }
    function validate_numeric($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->func_var('is_numeric',$name)
             ->end_assert();
        return $this;
    }
    function validate_integer($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->begin_op_var( $name, '==' )
                 ->func_var( 'round', $name )
               ->end_op_var()
             ->end_assert();
        return $this;
    }
    function validate_integer_range($name,$type) {
        list($min,$max) = $type->get_range();
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->begin_group()
                 ->op_var( $name, '>=', $min )
                 ->op('and')
                 ->op_var( $name, '<=', $max )
               ->end_group()
             ->end_assert();
    }
    function validate_boolean($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->func_var( 'is_bool', $name )
               ->op('or')
               ->op_var( $name, "===", 1 )
               ->op('or')
               ->op_var( $name, "===", 0 )
             ->end_assert();
        return $this;
    }
    function validate_nonnumeric($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->func_var('is_scalar',$name)
             ->end_assert();
        return $this;
    }
    function validate_string_length($name,$length) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->func_var('mb_strlen',$name)
               ->op('<=')
               ->add( $length )
             ->end_assert();
        return $this;
    }
    function validate_date($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->preg_match( '/^\d\d\d\d-\d\d-\d\d$/', $name )
             ->end_assert();
        return $this;
    }
    function validate_datetime($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->op_var($name,'===',0)
               ->op('or')
               ->op_var($name,'===',"'0'")
               ->op('or')
               ->preg_match( '/^(\d{1,4})-(\d\d?)-(\d\d?)(?: (\d\d?)(?::(\d\d?)(?::(\d\d?))?)?)?$/', $name )
             ->end_assert();
        return $this;
    }
    function validate_time($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->preg_match('/^\d\d(?::\d\d(?::\d\d)?)?$/' , $name)
             ->end_assert();
        return $this;
    }
    function validate_timestamp($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->op_var($name,'==',0)
               ->op('or')
               ->preg_match('/^\d{14}$/' , $name)
             ->end_assert();
        return $this;
    }
    function validate_year($name) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->op_var($name,'===',0)
               ->op('or')
               ->preg_match('/^\d\d(?:\d\d)?$/' , $name)
             ->end_assert();
        return $this;
    }
    function validate_enum($name,$values) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->begin_in_array( $name )
                 ->add_array( $values )
               ->end_in_array()
             ->end_assert();
        return $this;
    }
    function validate_set($name,$values) {
        $this->begin_assert()
               ->func_var('is_null',$name)
               ->op('or')
               ->begin_in_array( $name )
                 ->add_array( $values )
               ->end_in_array()
             ->end_assert();
        return $this;
    }
    function arg_validate_numeric(Modyllic_Schema_Arg $arg) {
        if ( $arg->type instanceOf Modyllic_Type_Boolean ) {
            $this->validate_boolean($arg->name);
        }
        else {
            $this->validate_numeric($arg->name);
            if ( $arg->type instanceOf Modyllic_Type_Integer ) {
                $this->validate_integer($arg->name);
                $this->validate_integer_range($arg->name,$arg->type);
            }
        }
        return $this;
    }
    function arg_validate_nonnumeric(Modyllic_Schema_Arg $arg) {
        $this->validate_nonnumeric( $arg->name );
        return $this;
    }
    function arg_validate_string(Modyllic_Schema_Arg $arg) {
        if ( isset($arg->type->length) ) {
            $this->validate_string_length($arg->name, $arg->type->length);
        }
        return $this;
    }
    function arg_validate_date(Modyllic_Schema_Arg $arg) {
        $this->validate_date($arg->name);
        return $this;
    }
    function arg_validate_datetime(Modyllic_Schema_Arg $arg) {
        $this->validate_datetime($arg->name);
        return $this;
    }
    function arg_validate_time(Modyllic_Schema_Arg $arg) {
        $this->validate_time($arg->name);
        return $this;
    }
    function arg_validate_timestamp(Modyllic_Schema_Arg $arg) {
        $this->validate_timestamp($arg->name);
        return $this;
    }
    function arg_validate_year(Modyllic_Schema_Arg $arg) {
        $this->validate_year($arg->name);
        return $this;
    }
    function arg_validate_enum(Modyllic_Schema_Arg $arg) {
        $this->validate_enum($arg->name, $arg->type->values);
        return $this;
    }
    function arg_validate_set(Modyllic_Schema_Arg $arg) {
        $this->validate_set($arg->name, $arg->type->values);
        return $this;
    }
    function arg_validate(Modyllic_Schema_Arg $arg) {
        if ( $arg->type instanceOf Modyllic_Type_Numeric ) {
            $this->arg_validate_numeric($arg);
        }
        else {
            $this->arg_validate_nonnumeric($arg);
        }
        if ( $arg->type instanceOf Modyllic_Type_String ) {
            $this->arg_validate_string( $arg );
        }
        if ( $arg->type instanceOf Modyllic_Type_Date ) {
            $this->arg_validate_date( $arg );
        }
        else if ( $arg->type instanceOf Modyllic_Type_Datetime ) {
            $this->arg_validate_datetime( $arg );
        }
        else if ( $arg->type instanceOf Modyllic_Type_Time ) {
            $this->arg_validate_time( $arg );
        }
        else if ( $arg->type instanceOf Modyllic_Type_Timestamp ) {
            $this->arg_validate_timestamp( $arg );
        }
        else if ( $arg->type instanceOf Modyllic_Type_Year ) {
            $this->arg_validate_year( $arg );
        }
        else if ( $arg->type instanceOf Modyllic_Type_Enum ) {
            $this->arg_validate_enum( $arg );
        }
        else if ( $arg->type instanceOf Modyllic_Type_Set ) {
            $this->arg_validate_set( $arg );
        }
        return $this;
    }
    function args_map_init(Modyllic_Schema_Routine $routine) {
        foreach ($routine->args as $arg) {
            $this->arg_map_assign($arg);
        }
        return $this;
    }
    function arg_map_assign(Modyllic_Schema_Arg $arg) {
        $name = $this->_arg_name($arg);
        $args_name = '$args[\''.$name.'\']';
        if ( $arg->dir == "INOUT" or $arg->dir=="OUT" ) {
            $this->begin_op_var( $arg->name, '=&' )
                   ->index('args', $name)
                 ->end_op_var();
        }
        else {
            $this->begin_op_var( $arg->name, '=' )
                   ->begin_cmd("isset(")
                     ->index('args', $name)
                   ->end_cmd(')')
                   ->add('? ')
                   ->index('args',$name)
                   ->add(': null')
                 ->end_op_var();
        }
        return $this;
    }
    function _arg_name(Modyllic_Schema_Arg $arg) {
        $name = $arg->name;
        if ( substr($name,0,2) == "p_" ) {
            $name = substr($name,2);
        }
        return $name;
    }
    function args_map_validate(Modyllic_Schema_Routine $routine) {
        $this->begin_assert_block()
               ->assign( 'is_ok', 'true' )
               ->begin_foreach( 'args', 'name', 'value' )
                 ->begin_if_expr()
                   ->add_not()
                   ->begin_in_array( 'name' )
                     ->arg_list_array($routine)
                   ->end_in_array()
                 ->end_if_expr()
                   ->assign( 'is_ok', 'false' )
                   ->add_break()
                 ->end_if()
               ->end_foreach()
               ->add_var( 'is_ok' )
             ->end_assert_block();
        return $this;
    }
    function arg_list_array(Modyllic_Schema_Routine $routine) {
        $this->begin_array();
        $argc = 0;
        foreach ($routine->args as $arg) {
            if ( $argc ++ ) {
                $this->sep();
            }
            $this->add_str( $this->_arg_name($arg ) );
        }
        $this->end_array();
        return $this;
    }
    function returns_docs(Modyllic_Schema_Routine $routine) {
        if ( $routine instanceOf Modyllic_Schema_Func ) {
            $this->func_returns_docs($routine->returns);
        }
        else if ( $routine instanceOf Modyllic_Schema_Proc ) {
            $this->proc_returns_docs($routine->returns);
        }
        return $this;
    }
    function func_returns_docs(Modyllic_Type $returns) {
        $this->phpdoc( "@returns ".$returns->to_sql());
        return $this;
    }
    function proc_returns_docs(array $returns) {
        switch ($returns['type']) {
            case "ROW":
                $this->phpdoc( "@returns row" );
                break;
            case "COLUMN":
                $this->phpdoc( "@returns ".$returns['column'] );
                break;
            case "LIST":
                $this->phpdoc( "@returns array of ".$returns['column'] );
                break;
            case "TABLE":
                $this->phpdoc( "@returns array of rows" );
                break;
            case "MAP":
                $this->phpdoc( "@returns array of ".$returns['key']." => ".
                    $returns['value'] );
                break;
            case "STH":
                $this->phpdoc( "@returns Statement handle" );
                break;
            case "NONE":
                break;
            default:
                throw new Exception("Unknown stored procedure return type: ".$returns['type']);
        }
        return $this;
    }

    function call_sql(Modyllic_Schema_Routine $routine) {
        if ( $routine instanceOf Modyllic_Schema_Func ) {
            $this->func_call_sql($routine);
        }
        else if ( $routine instanceOf Modyllic_Schema_Proc ) {
            $this->proc_call_sql($routine);
        }
        else {
            throw new Exception("Unknown type of stored routine: ".get_class($routine));
        }
        return $this;
    }

    function func_call_sql(Modyllic_Schema_Routine $routine) {
        $this->begin_sql_cmd( "SELECT" )
               ->begin_sql_func( $routine->name )
                 ->args_sql($routine)
               ->end_sql_func()
             ->end_sql_cmd();
        return $this;
    }

    function proc_call_sql(Modyllic_Schema_Routine $routine) {
        $this->begin_sql_cmd( "CALL" )
               ->begin_sql_func( $routine->name )
                 ->args_sql($routine)
               ->end_sql_func()
             ->end_sql_cmd();
        return $this;
    }

    function args_sql(Modyllic_Schema_Routine $routine) {
        $argc = 0;
        foreach ($routine->args as $arg) {
            if ( $argc ++ ) {
                $this->sep();
            }
            $this->arg_sql($arg);
        }
        return $this;
    }

    function arg_sql(Modyllic_Schema_Arg $arg) {
        $this->add(':'.$arg->name);
        return $this;
    }


    function bind_params(Modyllic_Schema_Routine $routine) {
        foreach ($routine->args as $arg) {
            $pdo_type = 'PDO::PARAM_STR';
            if ( $arg->type instanceOf Modyllic_Type_Integer ) {
                $pdo_type = 'PDO::PARAM_INT';
                if ( $arg->name == "BOOL" or $arg->name == "BOOLEAN" ) {
                    $php_type = "boolean";
                }
                else {
                    $php_type = "integer";
                }
            }
            else if ($arg->type instanceOf Modyllic_Type_Float) {
                $php_type = "float";
            }
            else if ($arg->type instanceOf Modyllic_Type_VarBinary or
                     $arg->type instanceOf Modyllic_Type_Binary or
                     $arg->type instanceOf Modyllic_Type_Blob) {
                $php_type = "binary";
            }
            else {
                $php_type = "string";
            }
            if ( $arg->dir == 'IN' ) {
                $method = 'bindValue';
            }
            else {
                $method = 'bindParam';
            }
            $this->begin_method('sth',$method)
                   ->begin_str()
                     ->arg_sql($arg)
                   ->end_str()
                   ->sep()
                   ->func_var("isset",$arg->name)
                     ->add("? ($php_type)")
                     ->add_var($arg->name)
                     ->add(": null")
                   ->sep()
                   ->add_const( $pdo_type )
                 ->end_method();
        }
        return $this;
    }
    function returns(Modyllic_Schema_Routine $routine) {
        if ( $routine instanceOf Modyllic_Schema_Func ) {
            $this->func_returns($routine);
        }
        else if ( $routine instanceOf Modyllic_Schema_Proc ) {
            $this->proc_returns($routine);
        }
        else {
            throw new Exception("Unknown type of stored routine: ".get_class($routine));
        }
        return $this;
    }
    function func_returns(Modyllic_Schema_Routine $routine) {
        $this->begin_list_assign('result')
               ->begin_method('sth','fetch')
                 ->add_const('PDO::FETCH_NUM')
               ->end_method()
             ->end_assign();
        $this->method('sth','closeCursor');
        $this->end_txns($routine);
        $this->add_return('result');
        return $this;
    }
    function proc_returns(Modyllic_Schema_Routine $routine) {
        $does_fetch = ! in_array( $routine->returns['type'], array( 'NONE', 'STH' ) );
        if ( $does_fetch ) {
            $this->begin_try();
        }
        $has_out = false;
        foreach ($routine->args as $arg) {
            if ( $arg->dir == 'INOUT' or $arg->dir == 'OUT' ) {
                $has_out = true;
                break;
            }
        }
        if ( $has_out ) {
            $this->begin_method('sth','setFetchMode')
                   ->add_const('PDO::FETCH_BOUND')
                   ->op('|')
                   ->add_const('PDO::FETCH_BOTH')
                 ->end_method();
        }
        switch ($routine->returns['type']) {
            case "ROW":
                $this->proc_returns_row($routine);
                break;
            case "COLUMN":
                $this->proc_returns_column($routine);
                break;
            case "LIST":
                $this->proc_returns_list($routine);
                break;
            case "TABLE":
                $this->proc_returns_table($routine);
                break;
            case "MAP":
                $this->proc_returns_map($routine);
                break;
            case "STH":
                $this->proc_returns_sth($routine);
                break;
            case "NONE":
                $this->proc_returns_none($routine);
                break;
            default:
                throw new Exception("Unknown proc return type: ".$routine->returns['type']);
        }
        if ( $does_fetch ) {
            $this->and_catch('PDOException')
                   ->begin_if_expr()
                     ->begin_cmd('strpos(')
                       ->method('e','getMessage')
                       ->sep()
                       ->add_str('SQLSTATE[HY000]: General error')
                     ->end_cmd(')')
                     ->op('!==')
                     ->add('false')
                   ->end_if_expr()
                     ->begin_throw()
                       ->begin_new('PDOException')
                         ->add_str('General error while fetching return value of '.$routine->name.
                             '; this usually means that you declared this routine as having a return value '.
                             'but it does not actually select any data before completing.')
                       ->end_new()
                     ->end_throw()
                   ->begin_else()
                     ->begin_throw()
                       ->add_var('e')
                     ->end_throw()
                   ->end_if()
                 ->end_try();
        }
        return $this;
    }
    function proc_returns_row(Modyllic_Schema_Routine $routine) {
        $this->begin_assign('result')
               ->begin_method('sth','fetch')
                 ->add_const('PDO::FETCH_ASSOC')
               ->end_method()
             ->end_assign();
        $this->method('sth','closeCursor');
        $this->end_txns($routine);
        $this->add_return('result');
        return $this;
    }
    function proc_returns_column(Modyllic_Schema_Routine $routine) {
        $this->begin_assign('row')
               ->begin_method('sth','fetch')
                 ->add_const('PDO::FETCH_ASSOC')
               ->end_method()
             ->end_assign();
        $this->method('sth','closeCursor');
        $this->begin_cmd( 'if (! isset(' )
               ->add_var('row')
             ->end_cmd(') ) {')
             ->begin_block()
               ->add_return()
             ->end_block('}');
        $this->begin_assert()
               ->begin_cmd( 'isset(' )
                 ->index( 'row', $routine->returns['column'] )
               ->end_cmd(')')
             ->end_assert();
        $this->end_txns($routine);
        $this->begin_return()
               ->index( 'row', $routine->returns['column'] )
             ->end_return();
        return $this;
    }
    function proc_returns_list(Modyllic_Schema_Routine $routine) {
        $this->begin_assign('results')
               ->add_array()
             ->end_assign();
        $this->begin_while_expr()
               ->begin_group()
                 ->begin_assign( 'row' )
                   ->begin_method('sth','fetch')
                     ->add_const('PDO::FETCH_ASSOC')
                   ->end_method()
                 ->end_assign()
               ->end_group()
               ->op('!==')
               ->add_false()
             ->end_while_expr()
               ->begin_assert()
                 ->begin_cmd( 'isset(' )
                   ->index( 'row', $routine->returns['column'] )
                 ->end_cmd(')')
               ->end_assert()
               ->begin_assign( 'results', '[]' )
                 ->index( 'row', $routine->returns['column'] )
               ->end_assign()
             ->end_while();
        $this->method('sth','closeCursor');
        $this->end_txns($routine);
        $this->add_return( 'results' );
        return $this;
    }
    function proc_returns_table(Modyllic_Schema_Routine $routine) {
        $this->begin_assign('table')
               ->begin_method('sth','fetchAll')
                 ->add_const('PDO::FETCH_ASSOC')
                ->end_method()
             ->end_assign();
        $this->method('sth','closeCursor');
        $this->end_txns($routine);
        $this->add_return( 'table' );
        return $this;
    }
    function proc_returns_map(Modyllic_Schema_Routine $routine) {
        $this->begin_assign('map')
               ->add_array()
             ->end_assign();
        $this->begin_while_expr()
               ->begin_group()
                 ->begin_assign('row')
                   ->begin_method('sth','fetch')
                     ->add_const('PDO::FETCH_ASSOC')
                   ->end_method()
                 ->end_assign()
               ->end_group()
               ->op('!==')
               ->add_false()
             ->end_while_expr();
        if ( $routine->returns['value'] != "ROW" ) {
          $this->begin_assert()
                 ->begin_cmd( 'isset(' )
                   ->index( 'row', $routine->returns['value'] )
                 ->end_cmd(')')
               ->end_assert();
        }
          $this->begin_cmd()
                 ->begin_index('map')
                   ->index('row',$routine->returns['key'])
                 ->end_index()
                 ->op('=');
        if ( $routine->returns['value'] == "ROW" ) {
            $this->add_var('row');
        }
        else {
            $this->index('row',$routine->returns['value']);
        }
          $this->end_cmd('',';')
             ->end_while();
        $this->method('sth','closeCursor');
        $this->end_txns($routine);
        $this->add_return('map');
        return $this;
    }
    function proc_returns_sth(Modyllic_Schema_Routine $routine) {
        $this->end_txns($routine);
        $this->add_return('sth');
        return $this;
    }
    function proc_returns_none(Modyllic_Schema_Routine $routine) {
        $this->method('sth','closeCursor');
        $this->end_txns($routine);
        return $this;
    }
}

