<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A base class for various schema objects.  Handles generic things like
 * providing previous values for the diff engine.  In a perfect world this
 * would be a runtime trait applied by the diff engine.
 */
class Modyllic_Diffable {
    public $from;
}
