<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

// then we can register our action
Console_CommandLine::registerAction('Dialect', 'Modyllic_Console_CommandLine_ActionDialect');

/// A Modyllic generator dialect
class Modyllic_Console_CommandLine_ActionDialect extends Console_CommandLine_Action {
    public function execute($value=false, array $params=array()) {
        $this->setResult( Modyllic_Generator::dialect_to_class($value) );
    }
}

