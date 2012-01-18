<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

/**
 * Helper class for commandline tools
 */

class SQL_Commandline {
    static function warn( $msg ) {
        $stderr = fopen('php://stderr','w');
        fwrite($stderr, $msg);
        fclose($stderr);
    }
    static function status( $pos, $len ) {
        if ( $len == 0 ) { return; }
        $percent = ($pos*100 / $len);
        $star_count = ($pos*40 / $len);
        $dash_count = 40-$star_count;
        static $source;
        if ( $source != SQL_Schema_Loader::$source ) {
            if (isset($source)) {
                self::warn("\r".str_repeat(" ",58+strlen($source))."\r");
            }
            $source = SQL_Schema_Loader::$source;
        }
        if(!function_exists('posix_isatty') or posix_isatty(0)) self::warn(sprintf("\rLoading %s [%s%s] %2.1f%%", 
            SQL_Schema_Loader::$source,
            str_repeat("*",$star_count), str_repeat("-",$dash_count),
            $percent ));
    }
    static function schema( $load ) {
        SQL_Tokenizer::on_advance( array( __CLASS__, "status" ) );
        try {
            ksort($load['args']);
            $schema = call_user_func_array(array("SQL_Schema_Loader",$load['kind']), $load['args'] );
        }
        catch (SQL_Exception $e) {
            echo "\r".$e->getMessage()."\n";
#            echo $e->getTraceAsString()."\n";
            exit(1);
        }
        catch (SQL_Schema_Loader_Exception $e) {
            echo "\r".$e->getMessage()."\n";
            exit(1);
        }
        return $schema;
    }
    
    static function next_step(&$steps) {
        if ( count($steps) ) {
            return array_shift($steps);
        }
        else {
            return "done";
        }
    }

    static function args( $cmd, $specs, $argv, $optspec=array() ) {
        array_shift($argv); // shift off the filename part
        
        $steps = explode(" ",$specs);
        $which = self::next_step($steps);
        $load = array();
        foreach ( $optspec as &$opt ) {
            $load[$opt] = 0;
        }
        $extra = array();

        while ( count($argv) ) {
            $arg = array_shift($argv);
            if ( in_array($arg, $optspec) ) {
                $load[$arg] ++;
                continue;
            }
            if ( $which == "done" ) {
                $extra[] = array_shift($argv);
                continue;
            }
            switch ($arg) {
                case "-h":
                    if ( isset($load[$which]['args'][0]) ) {
                        $which = self::next_step($steps);
                    }
                    $load[$which]['kind'] = "from_db";
                    $load[$which]['args'][0] = array_shift($argv);
                    break;
                case "-u":
                    if ( isset($load[$which]['args'][2]) ) {
                        $which = self::next_step($steps);
                    }
                    $load[$which]['kind'] = "from_db";
                    $load[$which]['args'][2] = array_shift($argv);
                    break;
                case "-p":
                    if ( isset($load[$which]['args'][3]) ) {
                        $which = self::next_step($steps);
                    }
                    $load[$which]['kind'] = "from_db";
                    $load[$which]['args'][3] = array_shift($argv);
                    break;
                case "-d":
                    if ( isset($load[$which]['args'][1]) ) {
                        $which = self::next_step($steps);
                    }
                    $load[$which]['kind'] = "from_db";
                    $load[$which]['args'][1] = array_shift($argv );
                    break;
                case "-f":
                    if ( isset($load[$which]['kind']) ) {
                        $which = self::next_step($steps);
                    }
                    $load[$which]['kind'] = "from_file";
                    $load[$which]['args'] = array(array_shift($argv)); 
                    $which = self::next_step($steps);
                    break;
                default:
                    // Special case for MySQL style passwords
                    if ( $arg[0] == "-" and $arg[1] == "p" ) {
                        $load[$which]['kind'] = "from_db";
                        $load[$which]['args'][3] = substr($arg, 2);
                        break;
                    }
                    // Unknown arguments terminate early
                    if ( $arg[0] == "-" ) {
                        $extra[] = $arg;
                        $which="done";
                        break;
                    }
                    else if ( ! isset($load[$which]['kind']) ) {
                        $load[$which]['kind'] = "from_file";
                        $load[$which]['args'] = array($arg);
                    }
                    else if ( $load[$which]['kind'] == "from_db" ) {
                        $load[$which]['args'][1] = $arg;
                    }
                    $which = self::next_step($steps);
            }
        }

        if ( $which != 'done' or count($extra) or count($steps) or !count($load) ) {
            echo "Form: $cmd";
            if ( count($optspec) ) {
                echo " [" . implode("] [",$optspec) . "]";
            }
            echo " $specs\n";
            echo "Where a spec is either:\n";
            echo "    [-f] filename\n";
            echo "or\n";
            echo "    [-h hostname] [-u username] [-p password] [-d] dbname\n";
            exit(1);
        }
        return $load;
    }
}
