<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/// A Modyllic generator dialect
class Modyllic_Console_CommandLine_ActionDialect extends Console_CommandLine_Action {
    public function execute($value=false, $params=array()) {
        $this->setResult( Modyllic_Generator::dialect_to_class($value) );
    }
}

