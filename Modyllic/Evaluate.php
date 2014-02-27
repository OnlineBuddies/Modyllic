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
        else if ($expr instanceOf Modyllic_Expression_Operator_Unary) {
            $op = $expr->op->token();
            if ($op == '!') {
                return ! self::exec($expr,$row);
            }
            else if ($op == '-') {
                return -1 * self::exec($expr,$row);
            }
            else {
                throw new Exception("Error while evaluating SQL, unsupported unary operator '$op'");
            }
        }
        else if ($expr instanceOf Modyllic_Expression_Operator_Binary) {
            $op = $expr->op->token();
            if ($op == 'AND' or $op == '&&') {
                return self::exec($expr->exp1,$row) and self::exec($expr->exp2,$row);
            }
            else if ($op == 'OR' or $op == '||') {
                return self::exec($expr->exp1,$row) or self::exec($expr->exp2,$row);
            }
            else if ($op == '=' or $op == '<=>') {
                return self::exec($expr->exp1,$row) == self::exec($expr->exp2,$row);
            }
            else {
                throw new Exception("Error while evaluating SQL, unsupported binary operator '$op'");
            }
        }
        else if ($expr instanceOf Modyllic_Expression_Function) {
            $func = $expr->func->token();
            if ($func == 'SUBSTR') {
                $value  = self::exec($expr->args[0],$row);
                $start  = self::exec($expr->args[1],$row)-1;
                $length = self::exec($expr->args[2],$row);
                if ($start <= Modyllic_Expression::MAX_SUBSTR_LENGTH and $length <= Modyllic_Expression::MAX_SUBSTR_LENGTH) {
                    return mb_substr( self::exec($expr->args[0],$row), self::exec($expr->args[1],$row)-1, self::exec($expr->args[2],$row), 'UTF-8' );
                }
                else {
                    throw new Exception("Error while evaluating SQL, substr start and length arguments must be less than or equal to ".Modyllic_Expression::MAX_SUBSTR_LENGTH);
                }
            }
            else {
                throw new Exception("Error while evaluating SQL, unsupported function '$func'");
            }
        }
    }
}
