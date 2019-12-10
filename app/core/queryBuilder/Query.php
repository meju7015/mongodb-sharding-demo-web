<?php

class Query extends Expression
{
     public $mode = 'select';

    public $defaultField = '*';

    protected $expression_class = 'Expression';

    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order][limit]';

    protected $template_insert = 'insert[option] into [table_noalias] ([set_fields]) values ([set_values])';

    protected $template_replace = 'replace[option] into [table_noalias] ([set_fields]) values ([set_values])';

    protected $template_delete = 'delete [from] [table_noalias][where][having]';

    protected $template_update = 'update [table_noalias] set [set] [where]';

    protected $template_truncate = 'truncate table [table_noalias]';

    protected $main_table = null;

    public function field($field, $alias = null)
    {
        // field is passed as string, may contain commas
        if (is_string($field) && strpos($field, ',') !== false) {
            $field = explode(',', $field);
        }

        // recursively add array fields
        if (is_array($field)) {
            if ($alias !== null) {
                throw new ModelException(
                    '$field 가 배열인 경우 key가 입력되지 않아야 합니다.',
                    500,
                    $alias
                );
            }

            foreach ($field as $alias => $f) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->field($f, $alias);
            }

            return $this;
        }

        // save field in args
        $this->_set_args('field', $alias, $field);

        return $this;
    }

    protected function _render_field($add_alias = true)
    {
        // will be joined for output
        $ret = [];

        // If no fields were defined, use defaultField
        if (empty($this->args['field'])) {
            if ($this->defaultField instanceof Expression) {
                return $this->_consume($this->defaultField);
            }

            return (string) $this->defaultField;
        }

        // process each defined field
        foreach ($this->args['field'] as $alias => $field) {
            // Do not add alias, if:
            //  - we don't want aliases OR
            //  - alias is the same as field OR
            //  - alias is numeric
            if (
                $add_alias === false
                || (is_string($field) && $alias === $field)
                || is_numeric($alias)
            ) {
                $alias = null;
            }

            // Will parameterize the value and escape if necessary
            $field = $this->_consume($field, 'soft-escape');

            if ($alias) {
                // field alias cannot be expression, so simply escape it
                $field .= ' '.$this->_escape($alias);
            }

            $ret[] = $field;
        }

        return implode(',', $ret);
    }

    /**
     * Renders part of the template: [field_noalias]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_field_noalias()
    {
        return $this->_render_field(false);
    }

    // }}}

    // {{{ Table specification and rendering

    /**
     * Specify a table to be used in a query.
     *
     * @param mixed  $table Specifies table
     * @param string $alias Specify alias for this table
     *
     * @return $this
     */
    public function table($table, $alias = null)
    {
        // comma-separated table names
        if (is_string($table) && strpos($table, ',') !== false) {
            $table = explode(',', $table);
        }

        // array of tables - recursively process each
        if (is_array($table)) {
            if ($alias !== null) {
                throw new ModelException(
                    '여러 테이블에 단일 별칭을 사용할 수 없습니다.',
                    500,
                    $alias
                );
            }

            foreach ($table as $alias => $t) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->table($t, $alias);
            }

            return $this;
        }

        // if table is set as sub-Query, then alias is mandatory
        if ($table instanceof self && $alias === null) {
            throw new ModelException(
                '테이블 이름이 누락되었습니다.',
                500
            );
        }

        if (is_string($table) && $alias === null) {
            $alias = $table;
        }

        // main_table will be set only if table() is called once.
        // it's used as "default table" when joining with other tables, see join().
        // on multiple calls, main_table will be false and we won't
        // be able to join easily anymore.
        $this->main_table = ($this->main_table === null && $alias !== null ? $alias : false);

        // save table in args
        $this->_set_args('table', $alias, $table);

        return $this;
    }

    protected function _render_table($add_alias = true)
    {
        // will be joined for output
        $ret = [];

        if (empty($this->args['table'])) {
            return '';
        }

        // process tables one by one
        foreach ($this->args['table'] as $alias => $table) {

            // throw exception if we don't want to add alias and table is defined as Expression
            if ($add_alias === false && $table instanceof self) {
                throw new ModelException(
                    'INSERT & UPDATE 에서 테이블 이름이 잘못된것 같습니다.',
                    500
                );
            }

            // Do not add alias, if:
            //  - we don't want aliases OR
            //  - alias is the same as table name OR
            //  - alias is numeric
            if (
                $add_alias === false
                || (is_string($table) && $alias === $table)
                || is_numeric($alias)
            ) {
                $alias = null;
            }

            // consume or escape table
            $table = $this->_consume($table, 'soft-escape');

            // add alias if needed
            if ($alias) {
                $table .= ' '.$this->_escape($alias);
            }

            $ret[] = $table;
        }

        return implode(',', $ret);
    }

    protected function _render_table_noalias()
    {
        return $this->_render_table(false);
    }

    /**
     * Renders part of the template: [from]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_from()
    {
        return empty($this->args['table']) ? '' : 'from';
    }

    /**
     * Joins your query with another table. Join will use $main_table
     * to reference the main table, unless you specify it explicitly.
     *
     * Examples:
     *  $q->join('address');         // on user.address_id=address.id
     *  $q->join('address.user_id'); // on address.user_id=user.id
     *  $q->join('address a');       // With alias
     *  $q->join(array('a'=>'address')); // Also alias
     *
     * Second argument may specify the field of the master table
     *  $q->join('address', 'billing_id');
     *  $q->join('address.code', 'code');
     *  $q->join('address.code', 'user.code');
     *
     * Third argument may specify which kind of join to use.
     *  $q->join('address', null, 'left');
     *  $q->join('address.code', 'user.code', 'inner');
     *
     * Using array syntax you can join multiple tables too
     *  $q->join(array('a'=>'address', 'p'=>'portfolio'));
     *
     * You can use expression for more complex joins
     *  $q->join('address',
     *      $q->orExpr()
     *          ->where('user.billing_id=address.id')
     *          ->where('user.technical_id=address.id')
     *  )
     *
     * @param string|array $foreign_table  Table to join with
     * @param mixed        $master_field   Field in master table
     * @param string       $join_kind      'left' or 'inner', etc
     * @param string       $_foreign_alias Internal, don't use
     *
     * @return $this
     */
    public function join(
        $foreign_table,
        $master_field = null,
        $join_kind = null,
        $_foreign_alias = null
    ) {
        // If array - add recursively
        if (is_array($foreign_table)) {
            foreach ($foreign_table as $alias => $foreign) {
                if (is_numeric($alias)) {
                    $alias = null;
                }

                $this->join($foreign, $master_field, $join_kind, $alias);
            }

            return $this;
        }
        $j = [];

        if ($_foreign_alias === null) {
            list($foreign_table, $_foreign_alias) = array_pad(explode(' ', $foreign_table, 2), 2, null);
        }

        list($f1, $f2) = array_pad(explode('.', $foreign_table, 2), 2, null);

        if (is_object($master_field)) {
            $j['expr'] = $master_field;
        } else {
            if ($master_field === null) {
                list($m1, $m2) = [null, null];
            } else {
                list($m1, $m2) = array_pad(explode('.', $master_field, 2), 2, null);
            }
            if ($m2 === null) {
                $m2 = $m1;
                $m1 = null;
            }
            if ($m1 === null) {
                $m1 = $this->main_table;
            }

            // Identify fields we use for joins
            if ($f2 === null && $m2 === null) {
                $m2 = $f1.'_id';
            }
            if ($m2 === null) {
                $m2 = 'id';
            }
            $j['m1'] = $m1;
            $j['m2'] = $m2;
        }

        $j['f1'] = $f1;
        if ($f2 === null) {
            $f2 = 'id';
        }
        $j['f2'] = $f2;

        $j['t'] = $join_kind ?: 'left';
        $j['fa'] = $_foreign_alias;

        $this->args['join'][] = $j;

        return $this;
    }

    public function _render_join()
    {
        if (!isset($this->args['join'])) {
            return '';
        }
        $joins = [];
        foreach ($this->args['join'] as $j) {
            $jj = '';

            $jj .= $j['t'].' join ';

            $jj .= $this->_escape($j['f1']);

            if ($j['fa'] !== null) {
                $jj .= ' as '.$this->_escape($j['fa']);
            }

            $jj .= ' on ';

            if (isset($j['expr'])) {
                $jj .= $this->_consume($j['expr']);
            } else {
                $jj .=
                    $this->_escape($j['fa'] ?: $j['f1']).'.'.
                    $this->_escape($j['f2']).' = '.
                    ($j['m1'] === null ? '' : $this->_escape($j['m1']).'.').
                    $this->_escape($j['m2']);
            }
            $joins[] = $jj;
        }

        return ' '.implode(' ', $joins);
    }

    public function where($field, $cond = null, $value = null, $kind = 'where', $num_args = null)
    {
        if ($num_args === null) {
            $num_args = func_num_args();
        }

        if (is_array($field)) {
            $or = $this->orExpr();
            foreach ($field as $row) {
                if (is_array($row)) {
                    call_user_func_array([$or, 'where'], $row);
                } else {
                    $or->where($row);
                }
            }
            $field = $or;
        }

        if ($num_args === 1 && is_string($field)) {
            $this->args[$kind][] = [$this->expr($field)];

            return $this;
        }

        if ($num_args === 2 && is_string($field) && !preg_match('/^[.a-zA-Z0-9_]*$/', $field)) {
            // field contains non-alphanumeric values. Look for condition
            preg_match(
                '/^([^ <>!=]*)([><!=]*|( *(not|is|in|like))*) *$/',
                $field,
                $matches
            );

            $value = $cond;
            $cond = $matches[2];

            if (!$cond) {
                $matches[1] = $this->expr($field);

                $cond = '=';
            } else {
                $num_args++;
            }

            $field = $matches[1];
        }

        switch ($num_args) {
            case 1:
                $this->args[$kind][] = [$field];
                break;
            case 2:
                if (is_object($cond) && !$cond instanceof Expressionable && !$cond instanceof Expression) {
                    throw new ModelException(
                        '값을 SQL 호환식으로 변경할수 없습니다.',
                        500,
                        "field:{$field}, value:{$value}"
                    );
                }

                $this->args[$kind][] = [$field, $cond];
                break;
            case 3:
                if (is_object($value) && !$value instanceof Expressionable && !$value instanceof Expression) {
                    throw new ModelException(
                        '값을 SQL 호환식으로 변경할수 없습니다.',
                        500,
                        "field:{$field}, cond:{$cond}, value:{$value}"
                    );
                }

                $this->args[$kind][] = [$field, $cond, $value];
                break;
        }

        return $this;
    }

    public function having($field, $cond = null, $value = null)
    {
        $num_args = func_num_args();

        return $this->where($field, $cond, $value, 'having', $num_args);
    }

    /**
     * Subroutine which renders either [where] or [having].
     *
     * @param string $kind 'where' or 'having'
     *
     * @return array Parsed chunks of query
     */
    protected function __render_where($kind)
    {
        // will be joined for output
        $ret = [];

        // where() might have been called multiple times. Collect all conditions,
        // then join them with AND keyword
        foreach ($this->args[$kind] as $row) {
            $ret[] = $this->__render_condition($row);
        }

        return $ret;
    }

    /**
     * Renders one condition.
     *
     * @param array $row Condition
     *
     * @return string
     */
    protected function __render_condition($row)
    {
        if (count($row) === 3) {
            list($field, $cond, $value) = $row;
        } elseif (count($row) === 2) {
            list($field, $cond) = $row;
        } elseif (count($row) === 1) {
            list($field) = $row;
        }

        $field = $this->_consume($field, 'soft-escape');

        if (count($row) == 1) {
            // Only a single parameter was passed, so we simply include all
            return $field;
        }

        // below are only cases when 2 or 3 arguments are passed

        // if no condition defined - set default condition
        if (count($row) == 2) {
            $value = $cond;

            if (is_array($value)) {
                $cond = 'in';
            } elseif ($value instanceof self && $value->mode === 'select') {
                $cond = 'in';
            } else {
                $cond = '=';
            }
        } else {
            $cond = trim(strtolower($cond));
        }

        // below we can be sure that all 3 arguments has been passed

        // special conditions (IS | IS NOT) if value is null
        if ($value === null) {
            if ($cond === '=') {
                $cond = 'is';
            } elseif (in_array($cond, ['!=', '<>', 'not'])) {
                $cond = 'is not';
            }
        }

        // value should be array for such conditions
        if (in_array($cond, ['in', 'not in', 'not']) && is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        // special conditions (IN | NOT IN) if value is array
        if (is_array($value)) {
            $value = '('.implode(',', $this->_param($value)).')';
            $cond = in_array($cond, ['!=', '<>', 'not', 'not in']) ? 'not in' : 'in';

            return $field.' '.$cond.' '.$value;
        }

        // if value is object, then it should be Expression or Query itself
        // otherwise just escape value
        $value = $this->_consume($value, 'param');

        return $field.' '.$cond.' '.$value;
    }

    /**
     * Renders [where].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_where()
    {
        if (!isset($this->args['where'])) {
            return;
        }

        return ' where '.implode(' and ', $this->__render_where('where'));
    }

    /**
     * Renders [orwhere].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_orwhere()
    {
        if (!isset($this->args['where'])) {
            return;
        }

        return implode(' or ', $this->__render_where('where'));
    }

    /**
     * Renders [andwhere].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_andwhere()
    {
        if (!isset($this->args['where'])) {
            return;
        }

        return implode(' and ', $this->__render_where('where'));
    }

    /**
     * Renders [having].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_having()
    {
        if (!isset($this->args['having'])) {
            return;
        }

        return ' having '.implode(' and ', $this->__render_where('having'));
    }

    // }}}

    // {{{ group()

    /**
     * Implements GROUP BY functionality. Simply pass either field name
     * as string or expression.
     *
     * @param mixed $group Group by this
     *
     * @return $this
     */
    public function group($group)
    {
        // Case with comma-separated fields
        if (is_string($group) && !$this->isUnescapablePattern($group) && strpos($group, ',') !== false) {
            $group = explode(',', $group);
        }

        if (is_array($group)) {
            foreach ($group as $g) {
                $this->args['group'][] = $g;
            }

            return $this;
        }

        $this->args['group'][] = $group;

        return $this;
    }

    /**
     * Renders [group].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_group()
    {
        if (!isset($this->args['group'])) {
            return '';
        }

        $g = array_map(function ($a) {
            return $this->_consume($a, 'soft-escape');
        }, $this->args['group']);

        return ' group by '.implode(', ', $g);
    }

    // }}}

    // {{{ Set field implementation

    /**
     * Sets field value for INSERT or UPDATE statements.
     *
     * @param string|array $field Name of the field
     * @param mixed        $value Value of the field
     *
     * @return $this
     */
    public function set($field, $value = null)
    {
        if ($value === false) {
            throw new ModelException(
                'false 값은 SQL에서 지원하지 않습니다.',
                500,
                "field:{$field}, value:{$value}"
            );
        }

        if (is_array($value)) {
            throw new ModelException(
                'array 값은 SQL에서 지원하지 않습니다.',
                500,
                "field:{$field}, value:{$value}"
            );
        }

        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->set($key, $value);
            }

            return $this;
        }

        if (is_string($field) || $field instanceof Expression || $field instanceof Expressionable) {
            $this->args['set'][] = [$field, $value];
        } else {
            throw new ModelException(
                '필드 이름은 string 이거나 Expressionable 되어야 합니다.',
                500,
                $field
            );
        }

        return $this;
    }

    /**
     * Renders [set] for UPDATE query.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set()
    {
        // will be joined for output
        $ret = [];

        if (isset($this->args['set']) && $this->args['set']) {
            foreach ($this->args['set'] as $val) {
                $field = $val[0];
                $value = $val[1];

                $field = $this->_consume($field, 'escape');
                $value = $this->_consume($value, 'param');

                $ret[] = $field.'='.$value;
            }
        }

        return implode(', ', $ret);
    }

    /**
     * Renders [set_fields] for INSERT.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set_fields()
    {
        // will be joined for output
        $ret = [];

        if ($this->args['set']) {
            foreach ($this->args['set'] as $val) {
                $field = $val[0];
                $field = $this->_consume($field, 'escape');

                $ret[] = $field;
            }
        }

        return implode(',', $ret);
    }

    /**
     * Renders [set_values] for INSERT.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set_values()
    {
        // will be joined for output
        $ret = [];

        if ($this->args['set']) {
            foreach ($this->args['set'] as $val) {
                $value = $val[1];
                $value = $this->_consume($value, 'param');

                $ret[] = $value;
            }
        }

        return implode(',', $ret);
    }

    // }}}

    // {{{ Option

    /**
     * Set options for particular mode.
     *
     * @param mixed  $option
     * @param string $mode   select|insert|replace
     *
     * @return $this
     */
    public function option($option, $mode = 'select')
    {
        // Case with comma-separated options
        if (is_string($option) && strpos($option, ',') !== false) {
            $option = explode(',', $option);
        }

        if (is_array($option)) {
            foreach ($option as $opt) {
                $this->args['option'][$mode][] = $opt;
            }

            return $this;
        }

        $this->args['option'][$mode][] = $option;

        return $this;
    }

    /**
     * Renders [option].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_option()
    {
        if (!isset($this->args['option'][$this->mode])) {
            return '';
        }

        return ' '.implode(' ', $this->args['option'][$this->mode]);
    }

    // }}}

    // {{{ Query Modes

    public function select()
    {
        return $this->mode('select')->execute($this->slaveConnection);
    }

    public function insert()
    {
        return $this->mode('insert')->execute($this->connection);
    }

    public function update()
    {
        return $this->mode('update')->execute($this->connection);
    }

    public function replace()
    {
        return $this->mode('replace')->execute($this->connection);
    }

    public function delete()
    {
        return $this->mode('delete')->execute($this->connection);
    }

    public function truncate()
    {
        return $this->mode('truncate')->execute($this->connection);
    }

    // }}}

    // {{{ Limit

    public function limit($cnt, $shift = null)
    {
        $this->args['limit'] = [
            'cnt'   => $cnt,
            'shift' => $shift,
        ];

        return $this;
    }

    public function _render_limit()
    {
        if (isset($this->args['limit'])) {
            return ' limit '.
                (int) $this->args['limit']['shift'].
                ', '.
                (int) $this->args['limit']['cnt'];
        }
    }

    // }}}

    // {{{ Order

    public function order($order, $desc = null)
    {
        // Case with comma-separated fields or first argument being an array
        if (is_string($order) && strpos($order, ',') !== false) {
            $order = explode(',', $order);
        }

        if (is_array($order)) {
            if ($desc !== null) {
                throw new ModelException(
                    'argument 의 첫번째 값이 배열이면, 두번째 값이 없어야 합니다.',
                    500
                );
            }
            foreach (array_reverse($order) as $o) {
                $this->order($o);
            }

            return $this;
        }

        if ($desc === null && is_string($order) && strpos($order, ' ') !== false) {
            $_chunks = explode(' ', $order);
            $_desc = strtolower(array_pop($_chunks));
            if (in_array($_desc, ['desc', 'asc'])) {
                $order = implode(' ', $_chunks);
                $desc = $_desc;
            }
        }

        if (is_bool($desc)) {
            $desc = $desc ? 'desc' : '';
        } elseif (strtolower($desc) === 'asc') {
            $desc = '';
        } else {
        }

        $this->args['order'][] = [$order, $desc];

        return $this;
    }

    public function _render_order()
    {
        if (!isset($this->args['order'])) {
            return'';
        }

        $x = [];
        foreach ($this->args['order'] as $tmp) {
            list($arg, $desc) = $tmp;
            $x[] = $this->_consume($arg, 'soft-escape').($desc ? (' '.$desc) : '');
        }

        return ' order by '.implode(', ', array_reverse($x));
    }

    // }}}

    public function __debugInfo()
    {
        $arr = [
            'R'          => false,
            'mode'       => $this->mode,
            //'template'   => $this->template,
            //'params'     => $this->params,
            //'connection' => $this->connection,
            //'main_table' => $this->main_table,
            //'args'       => $this->args,
        ];

        try {
            $arr['R'] = $this->getDebugQuery();
        } catch (\Exception $e) {
            $arr['R'] = $e->getMessage();
        }

        return $arr;
    }

    public function render()
    {
        if (!$this->template) {
            $this->mode('select');
        }

        return parent::render();
    }

    public function mode($mode)
    {
        $prop = 'template_'.$mode;

        if (isset($this->{$prop})) {
            $this->mode = $mode;
            $this->template = $this->{$prop};
        } else {
            throw new ModelException(
                'template 모드가 누락되었습니다.',
                500,
                $mode
            );
        }

        return $this;
    }

    public function dsql($properties = [])
    {
        $q = new static($properties);
        $q->connection = $this->connection;

        return $q;
    }

    /**
     * Returns Expression object for the corresponding Query
     * sub-class (e.g. Query_MySQL will return Expression_MySQL).
     *
     * Connection is not mandatory, but if set, will be preserved. This
     * method should be used for building parts of the query internally.
     *
     * @param array $properties
     * @param array $arguments
     *
     * @return Expression
     */
    public function expr($properties = [], $arguments = null)
    {
        $c = $this->expression_class;
        $e = new $c($properties, $arguments);
        $e->connection = $this->connection;

        return $e;
    }

    /**
     * Returns new Query object of [or] expression.
     *
     * @return Query
     */
    public function orExpr()
    {
        return $this->dsql(['template' => '[orwhere]']);
    }

    /**
     * Returns new Query object of [and] expression.
     *
     * @return Query
     */
    public function andExpr()
    {
        return $this->dsql(['template' => '[andwhere]']);
    }

    /**
     * Returns Query object of [case] expression.
     *
     * @param mixed $operand Optional operand for case expression.
     *
     * @return Query
     */
    public function caseExpr($operand = null)
    {
        $q = $this->dsql(['template' => '[case]']);

        if ($operand !== null) {
            $q->args['case_operand'] = $operand;
        }

        return $q;
    }

    public function groupConcat($field, $delimeter = ',')
    {
        throw new ModelException(
            'groupConcat() 에서 corrent class 는 SQL호환이 아닙니다.',
            500
        );
    }

    /**
     * Add when/then condition for [case] expression.
     *
     * @param mixed $when Condition as array for normal form [case] statement or just value in case of short form [case] statement
     * @param mixed $then Then expression or value
     *
     * @return $this
     */
    public function when($when, $then)
    {
        $this->args['case_when'][] = [$when, $then];

        return $this;
    }

    /**
     * Add else condition for [case] expression.
     *
     * @param mixed $else Else expression or value
     *
     * @return $this
     */
    //public function else($else) // PHP 5.6 restricts to use such method name. PHP 7 is fine with it
    public function otherwise($else)
    {
        $this->args['case_else'] = $else;

        return $this;
    }

    /**
     * Renders [case].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_case()
    {
        if (!isset($this->args['case_when'])) {
            return;
        }

        $ret = '';

        // operand
        if ($short_form = isset($this->args['case_operand'])) {
            $ret .= ' '.$this->_consume($this->args['case_operand'], 'soft-escape');
        }

        // when, then
        foreach ($this->args['case_when'] as $row) {
            if (!array_key_exists(0, $row) || !array_key_exists(1, $row)) {
                throw new ModelException(
                    'method 파라미터로 when 을 이용할수 없습니다.',
                    500,
                    $row
                );
            }

            $ret .= ' when ';
            if ($short_form) {
                // short-form
                if (is_array($row[0])) {
                    throw new ModelException(
                        '짧은 형식의 CASE 문을 사용할 때는 when () 메서드의 첫 번째 매개 변수로 배열을 설정하면 안됩니다.',
                        500,
                        $row[0]
                    );
                }
                $ret .= $this->_consume($row[0], 'param');
            } else {
                $ret .= $this->__render_condition($row[0]);
            }

            // then
            $ret .= ' then '.$this->_consume($row[1], 'param');
        }

        // else
        if (array_key_exists('case_else', $this->args)) {
            $ret .= ' else '.$this->_consume($this->args['case_else'], 'param');
        }

        return ' case'.$ret.' end';
    }

    protected function _set_args($what, $alias, $value)
    {
        // save value in args
        if ($alias === null) {
            $this->args[$what][] = $value;
        } else {

            // don't allow multiple values with same alias
            if (isset($this->args[$what][$alias])) {
                throw new ModelException(
                    '별칭은 유니크해야 합니다.',
                    500,
                    "what:{$what}, alias:{$alias}"
                );
            }

            $this->args[$what][$alias] = $value;
        }
    }

    /// }}}
}
