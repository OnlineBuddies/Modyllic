<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package OLB::SQL
 * @author bturner@online-buddies.com
 */

class SQL_Generator_StrictSQL extends SQL_Generator_SQL {
    // We include weak constraints as well as regular ones
    function ignore_index( $index ) {
        return FALSE;
    }
}
