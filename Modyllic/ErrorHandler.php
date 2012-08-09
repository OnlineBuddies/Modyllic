<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Installs an error handler that lets us trac
 */
class Modyllic_ErrorHandler {
    public static $last_message = "";
    public static $last_error = null;

    static function install() {
        assert_options( ASSERT_ACTIVE, 1 );
        assert_options( ASSERT_BAIL, 0 );
        assert_options( ASSERT_WARNING, 1 );

        set_error_handler(array(__CLASS__,"handle_error"), E_ALL | E_STRICT );
    }

    static function handle_error($type=0, $message="", $file="", $line=-1) {
        self::$last_message = $message;
        self::$last_error = new ErrorException( $message, $type, 0, $file, $line );
        if ( error_reporting() & $type ) {
            throw self::$last_error;
        }
    }
}
