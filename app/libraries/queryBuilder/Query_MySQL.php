<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오후 2:01
 */
class Query_MySQL extends Query
{
    protected $escape_char = '`';
    protected $expression_class = 'Expression_MySQL';
    protected $template_update = 'update [table][join] set [set] [where]';

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('group_concat({} separator [])', Array($field, $delimeter));
    }
}