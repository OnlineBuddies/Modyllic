<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Things that look like numbers
 */
class Modyllic_Token_Num extends Modyllic_Token {
   function debug() {
       return get_class($this).":".$this->value();
   }
}
