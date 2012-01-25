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
    static $isatty = false;
    static $in_file = "";

    static function init() {
        if ( function_exists('posix_isatty') and posix_isatty(STDOUT) ) {
            self::$isatty = true;
        }
        if ( ($cols = exec('tput cols')) !== FALSE ) {
            self::$width = $cols;
        }
    }

    static function warn( $msg ) {
        fwrite(STDERR, $msg);
    }
    static function clear_progress() {
        if ( self::$progress ) {
            self::warn("\r".str_repeat(" ",self::$width-1)."\r");
        }
    }

    static function status( $filename, $filenum, $filecount, $pos, $len ) {
        if ( self::$verbose and self::$in_file != $filename ) {
             if ( self::$progress and self::$in_file != "" ) {
                 self::clear_progress();
             }
             self::warn("Loading $filename...\n");
             self::$in_file = $filename;
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

        # if we can fit the entire filename on the line then we size the progress bar
        if ( $min_width+strlen($filename) < (self::$width-1) ) {
            $progress_size = self::$width - ($min_width+strlen($filename)+1);
        }
        else {
            $filename = substr($filename, 0, self::$width - ($min_width+1) );
        }

        $percent_per_file = 1 / $filecount;
        $already_done = ($filenum-1) * $percent_per_file;
        $in_file = $pos / $len;
        $overall = $already_done + $percent_per_file*$in_file;

        $fill_count = floor($overall * $progress_size);
        $blank_count = ceil($progress_size - $fill_count);

        $fill = str_repeat("*",$fill_count);
        $blank = str_repeat("-",$blank_count);

        self::warn(sprintf("\rLoading %s [%s%s] %2.1f%%", $filename, $fill, $blank, $overall*100 ));
    }
}

Modyllic_Status::init();
