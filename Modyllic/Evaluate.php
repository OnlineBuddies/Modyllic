<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

// This is a very minimal SQL expression evaluate.  It currently implements
// just enough to allow us to evaluate very simple where clauses.  These are used
// to find matching rows when applying metadata to table rows.
class Modyllic_Evaluate {
    static function exec($expr,$row) {
        if ($expr instanceOf Modyllic_Expression_Value) {
            if ($expr->token instanceOf Modyllic_Token_Ident and $expr->token->is_ident()) {
                return self::exec($row[$expr->token->unquote()],$row);
            }
            else {
                return $expr->token->unquote();
            }
        }
        if ($expr instanceOf Modyllic_Expression_Operator_Unary) {
            $op = $expr->op->token();
            if ($op == '!') {
                return ! self::exec($expr,$row);
            }
            else if ($op == '-') {
                return -1 * self::exec($expr,$row);
            }
        }
        if ($expr instanceOf Modyllic_Expression_Operator_Binary) {
            $op = $expr->op->token();
            if ($op == 'AND' or $op == '&&') {
                return self::exec($expr->exp1,$row) and self::exec($expr->exp2,$row);
            }
            if ($op == 'OR' or $op == '||') {
                return self::exec($expr->exp1,$row) or self::exec($expr->exp2,$row);
            }
            if ($op == '=' or $op == '<=>') {
                return self::exec($expr->exp1,$row) == self::exec($expr->exp2,$row);
            }
        }
    }
}
