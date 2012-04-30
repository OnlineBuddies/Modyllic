<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Helper class for commandline tools
 */

require_once "Modyllic/Tokenizer.php";
require_once "Modyllic/Loader.php";
require_once "Modyllic/Status.php";
require_once "Modyllic/Generator.php";

require_once "Console/CommandLine.php";
require_once "Console/CommandLine/Action.php";

// A Modyllic generator dialect
class Modyllic_Console_CommandLine_ActionDialect extends Console_CommandLine_Action {
    public function execute($value=false, $params=array()) {
        $this->setResult( Modyllic_Generator::dialect_to_class($value) );
    }
}

// then we can register our action
Console_CommandLine::registerAction('Dialect', 'Modyllic_Console_CommandLine_ActionDialect');

class Modyllic_CommandLine {

    static function get_parser() {
        static $parser;
        if ( !isset($parser) ) {
            $parser = new Console_CommandLine();
        }
        return $parser;
    }

    static function display_error( $msg ) {
        self::get_parser()->displayError( $msg );
    }

    static function get_args( $arg_spec ) {
        global $argv;
        if ( in_array("--version",$argv) ) {
            fputs(STDERR,"Modyllic Version: @VERSION@ @STATE@\n");
            exit();
        }
        $parser = self::get_parser();
        $parser->addOption('verbose', array(
            'short_name'  => '-v',
            'long_name'   => '--verbose',
            'description' => 'report each stage of execution to stderr',
            'action'      => 'Counter',
            'default'     => 0,
            ));
        $parser->addOption('debug', array(
            'long_name'   => '--debug',
            'description' => 'enables further diagnostic information for debugging the parser (useful for bug reports!)',
            'action'      => 'StoreTrue',
            'default'     => false,
            ));
        $parser->addOption('progress', array(
            'long_name'   => '--progress',
            'description' => 'output a progress meter to stderr',
            'action'      => 'StoreTrue',
            'default'     => false,
            ));
        // This is only in here to be included in help... we short circuit above if it's found
        $parser->addOption('version', array(
            'long_name'   => '--version',
            'description' => 'show the Modyllic version number',
            'action'      => 'StoreTrue',
            'default'     => false,
            ));
        if ( isset($arg_spec['description']) ) {
            $parser->description = $arg_spec['description'];
        }
        if ( isset($arg_spec['options']) ) {
            foreach ($arg_spec['options'] as $name=>$opt) {
                $parser->addOption( $name, $opt );
            }
        }
        if ( isset($arg_spec['arguments']) ) {
            foreach ($arg_spec['arguments'] as $name=>$opt) {
                $parser->addArgument( $name, $opt );
            }
        }
        try {
            $args = $parser->parse();
        }
        catch (Exception $e) {
            $parser->displayError($e->getMessage());
        }
        

        Modyllic_Status::$verbose = $args->options['verbose'];
        Modyllic_Status::$progress = $args->options['progress'];
        Modyllic_Status::$debug = $args->options['debug'];
        return $args;
    }

    static function schema( array $load ) {
        Modyllic_Tokenizer::on_advance( array( "Modyllic_Status", "status" ) );
        try {
            $schema = Modyllic_Loader::load( $load );
        }
        catch (Modyllic_Exception $e) {
            Modyllic_Status::clear_progress();
            Modyllic_Status::warn($e->getMessage()."\n");
            if ( Modyllic_Status::$debug ) {
                Modyllic_Status::warn($e->getTraceAsString()."\n");
            }
            exit(1);
        }
        catch (Modyllic_Loader_Exception $e) {
            Modyllic_Status::clear_progress();
            Modyllic_Status::warn($e->getMessage()."\n");
            exit(1);
        }
        catch (Exception $e) {
            Modyllic_Status::clear_progress();
            throw $e;
        }
        Modyllic_Status::clear_progress();
        return $schema;
    }

}
