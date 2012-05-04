<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * The tokenizer doesn't produce token lists itself, but parsers may find it
 * useful to clump up a bunch of tokens and reinject them as a single token.
 * This is here to provide that facility.
 */
class Modyllic_Token_List extends Modyllic_Token { }
