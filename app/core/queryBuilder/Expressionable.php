<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오후 1:22
 */
interface Expressionable
{
    public function getDSQLExpression($expression);
}