<?php
/**
 * Extensions to Lime that we use
 *
 * Copyright © 2010 Online Buddies, Inc. - All Rights Reserved
 *
 * @author bturner@online-buddies.com
 * @package mh_lime
 */

require_once "lime.php";

class mh_test extends lime_test {

    // @var string This is the message to include by default with the
    // next test.
    private $next_test;
    
    /**
     * Stores a message, in preparation for a try/catch pair.  Typically
     * you wouldn't pass a message to the assertion in those two places
     * so that they'll pick their message up from here. This way they can
     * show up as a single test in the results.
     * @param string $msg
     */
    public function try_test($msg) {
        $this->next_test = $msg;
    }

    /**
     * Wraps the ok assertion to support default messages.  As other tests
     * are implemented in terms of the ok assertion, this means that we
     * get this addition across the board.
     * @param bool $exp
     * @param string $message default ""
     */
    public function ok($exp, $message='') {
        if ( $message == "" ) {
            $message = $this->next_test;
            $this->next_test = "";
        }
        return parent::ok($exp,$message);
    }

    /**
     * An alias for ok
     * @param bool $exp
     * @param string $message default ""
     */
    public function is_true($exp, $message='') {
        return $this->ok($exp,$message);
    }
    
    /**
     * Negated ok, for times that produces cleaner code.
     * @param bool $exp
     * @param string $message default ""
     */
    public function is_false($exp, $message='') {
        return $this->ok(!$exp,$message);
    }
    
    /**
     * Asserts that $needle is in $haystack
     * @param mixed $needle
     * @param array $haystack
     * @param string message default ""
     */
    public function in_array( $needle, $haystack, $message='' ) {
        if (! $this->ok(in_array( $needle, $haystack), $message ) ) {
            $needle_str = $needle;
            if ( ! is_string($needle_str) ) {
                $needle_str = json_encode($needle_str);
            }
            $this->set_last_test_errors(array(
                "        needle: ".$needle_str,
                "      haystack: ".json_encode($haystack),
                ));
        }
    }
    /**
     * Asserts that $needle IS NOT in $haystack
     * @param mixed $needle
     * @param array $haystack
     * @param string message default ""
     */
    public function not_in_array( $needle, $haystack, $message='' ) {
        if (! $this->ok(! in_array( $needle, $haystack ), $message ) ) {
            $needle_str = $needle;
            if ( ! is_string($needle_str) ) {
                $needle_str = json_encode($needle_str);
            }
            $this->set_last_test_errors(array(
                "        needle: ".$needle_str,
                "      haystack: ".json_encode($haystack),
                ));
        }
    }
    
    /**
     * Asserts that $key exists in $array
     * @param mixed $needle
     * @param array $haystack
     * @param string message default ""
     */
    public function key_exists( $key, $array, $message='' ) {
        if (! $this->ok(array_key_exists( $key, $array), $message ) ) {
            $key_str = $key;
            if ( ! is_string($key_str) ) {
                $key_str = json_encode($key_str);
            }
            $this->set_last_test_errors(array(
                "           key: ".$key_str,
                "         array: ".json_encode($array),
                ));
        }
    }
    /**
     * Asserts that $needle IS NOT in $haystack
     * @param mixed $needle
     * @param array $haystack
     * @param string message default ""
     */
    public function key_not_exists( $key, $array, $message='' ) {
        if (! $this->ok(! array_key_exists( $key, $array ), $message ) ) {
            $key_str = $key;
            if ( ! is_string($key_str) ) {
                $key_str = json_encode($key_str);
            }
            $this->set_last_test_errors(array(
                "           key: ".$key_str,
                "         array: ".json_encode($array),
                ));
        }
    }

    /**
     * Asserts that $exp is null
     * @param mixed $exp
     * @param string message default ""
     */
    public function is_null($exp, $message) {
        if ( ! $this->ok( is_null($exp), $message ) ) {
            $this->set_last_test_errors(array(
                "      ".var_export($exp,true),
                "      isn't null",
                ));
        }
    }

    /**
     * Asserts that $exp is NOT null
     * @param mixed $exp
     * @param string message default ""
     */
    public function is_not_null($exp, $message) {
        if ( ! $this->ok( ! is_null($exp), $message ) ) {
            $this->set_last_test_errors(array(
                "      got null when expecting a non-null value",
                ));
        }
    }

    /**
     * Intended for use in a catch block, this takes an exception object
     * as an argument and triggers a test failure with the exception object's
     * message as the diagnostic.
     * @param Exception $e
     */
    public function except_fail($e) {
        $this->fail();
        $this->set_last_test_errors(array("     exception: [".get_class($e)."] ".$e->getMessage()));
    }

    /**
      * This let's you set a plan after the test object was created. 
      * Normally this is only needed for functional tests.
      * @param int $plan
      */
    public function plan($plan) {
        if ($this->results['stats']['plan'] !== null) {
            $this->output->red_bar(sprintf("# Tried to plan a second time, for %d tests.", $plan));
        }
        else {
            $this->results['stats']['plan'] = $plan;
            $this->output->echoln(sprintf("1..%d", $plan));
        }
    }

}


?>
