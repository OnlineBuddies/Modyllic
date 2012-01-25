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

require_once "Modyllic/Status.php";

require_once "Console/CommandLine.php";
require_once "Console/CommandLine/Action.php";

// This is our "Array" type action, often used for specs
class Modyllic_Console_CommandLine_ActionArray extends Console_CommandLine_Action {
    public function execute($value=false, $params=array()) {
        $result = $this->getResult();
        if ( !isset($result) ) {
            $result = array();
        }
        $result[] = $value;
        $this->setResult( $result );
    }
}

// then we can register our action
Console_CommandLine::registerAction('Array', 'Modyllic_Console_CommandLine_ActionArray');

class Modyllic_Commandline {
    static function warn( $msg ) {
        $stderr = fopen('php://stderr','w');
        fwrite($stderr, $msg);
        fclose($stderr);
    }

    static function status( $pos, $len ) {
        Modyllic_Status::status( Modyllic_Schema_Loader::$source, 1, 1, $pos, $len );
    }

    static function schema( $load ) {
        Modyllic_Tokenizer::on_advance( array( __CLASS__, "status" ) );
        try {
            $schema = Modyllic_Schema_Loader::load( $load );
        }
        catch (Modyllic_Exception $e) {
            Modyllic_Status::clear_progress();
            Modyllic_Status::warn($e->getMessage()."\n");
#            echo $e->getTraceAsString()."\n";
            exit(1);
        }
        catch (Modyllic_Schema_Loader_Exception $e) {
            Modyllic_Status::clear_progress();
            Modyllic_Status::warn($e->getMessage()."\n");
            exit(1);
        }
        catch (Exception $e) {
            Modyllic_Status::clear_progress();
            throw $e;
        }
        return $schema;
    }

}
