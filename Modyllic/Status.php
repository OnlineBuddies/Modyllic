<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Status {
    static $width = 80;
    static $verbose = 0;
    static $progress = false;
    static $debug = false;
    static $in_file = "";

    static function init() {
        if ( getenv('TERM') and ($cols = exec('tput cols')) !== false ) {
            self::$width = $cols;
        }
    }

    static function warn( $msg ) {
        fwrite(STDERR, $msg);
    }

    static function debug( $msg ) {
        if ( self::$debug ) {
            self::warn($msg);
        }
    }

    static function verbose( $msg ) {
        if ( self::$verbose ) {
            self::warn($msg);
        }
    }

    static function clear_progress() {
        if ( self::$progress ) {
            self::warn("\r".str_repeat(" ",self::$width-1)."\r");
        }
    }

    static $sourceName = "";
    static $sourceIndex = 0;
    static $sourceCount = 0;

    static function status( $pos, $len ) {
        if ( self::$in_file != self::$sourceName ) {
             if ( self::$in_file != "" ) {
                 self::clear_progress();
             }
             self::verbose("Loading ".self::$sourceName."...\n");
             self::$in_file = self::$sourceName;
        }

        if ( ! self::$progress ) {
            return;
        }

        $progress_size = 2;
        $min_width = 8 # "Loading "
                   + 2 # " ["
                   + $progress_size
                   + 2 # " ]"
                   + 5 # percentage
                   + 1;# "%"
        $min_filename_length = 3;

        # if there's no way to fit the progress bar on this screen, just return
        if ( $min_width+$min_filename_length >= self::$width ) {
            self::$progress = false;
            return;
        }

        $filename = self::$sourceName;

        # if we can fit the entire filename on the line then we size the progress bar
        if ( $min_width+strlen(self::$sourceName) < (self::$width-1) ) {
            $progress_size = self::$width - ($min_width+strlen($filename)+1);
        }
        else {
            $filename = substr($filename, 0, self::$width - ($min_width+1) );
        }

        $percent_per_file = 1 / self::$sourceCount;
        $already_done = (self::$sourceIndex-1) * $percent_per_file;
        $in_file = $len==0 ? 1 : ($pos / $len);
        $overall = $already_done + $percent_per_file*$in_file;

        $fill_count = floor($overall * $progress_size);
        $blank_count = ceil($progress_size - $fill_count);

        $fill = str_repeat("*",$fill_count);
        $blank = str_repeat("-",$blank_count);

        self::warn(sprintf("\rLoading %s [%s%s] %2.1f%%", $filename, $fill, $blank, $overall*100 ));
    }
}

Modyllic_Status::init();
