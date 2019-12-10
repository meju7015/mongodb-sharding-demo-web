<?php

// vim:ts=4:sw=4:et:fdm=marker

/**
 * Creates new expression. Optionally specify a string - a piece
 * of SQL code that will become expression template and arguments.
 *
 * See below for call patterns
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Expression implements \ArrayAccess, \IteratorAggregate, ResultSet
{
    /**
     * Template string.
     *
     * @var string
     */
    protected $template = null;

    /**
     * Hash containing configuration accumulated by calling methods
     * such as Query::field(), Query::table(), etc.
     *
     * $args['custom'] is used to store hash of custom template replacements.
     *
     * This property is made public to ease customization and make it accessible
     * from Connection class for example.
     *
     * @var array
     */
    public $args = ['custom' => []];

    /**
     * As per PDO, _param() will convert value into :a, :b, :c .. :aa .. etc.
     *
     * @var string
     */
    protected $paramBase = 'a';

    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '`';

    /**
     * Used for Linking.
     *
     * @var string
     */
    public $_paramBase = null;

    /**
     * Will be populated with actual values by _param().
     *
     * @var array
     */
    public $params = [];

    /**
     * When you are willing to execute the query, connection needs to be specified.
     * By default this is PDO object.
     *
     * @var \PDO|Connection
     */
    public $connection = null;

    public $slaveConnection = null;

    /**
     * Holds references to bound parameter values.
     *
     * This is needed to use bindParam instead of bindValue and to be able to use 4th parameter of bindParam.
     *
     * @var array
     */
    private $boundValues = [];

    public function __construct($properties = [], $arguments = null)
    {
        // save template
        if (is_string($properties)) {
            $properties = ['template' => $properties];
        } elseif (!is_array($properties)) {
            throw new ModelException(
                'Expression 생성자에서 문제가 발생했습니다.',
                500,
                "properties:{$properties}, arguments:{$arguments}"
            );
        }

        // supports passing template as property value without key 'template'
        if (isset($properties[0])) {
            $properties['template'] = $properties[0];
            unset($properties[0]);
        }

        // save arguments
        if ($arguments !== null) {
            if (!is_array($arguments)) {
                throw new ModelException(
                    'Expression arguments 는 배열형이여야 합니다.',
                    500,
                    "properties:{$properties}, arguments:{$arguments}"
                );
            }
            $this->args['custom'] = $arguments;
        }

        // deal with remaining properties
        foreach ($properties as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Casting to string will execute expression and return getOne() value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getOne();
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param string The offset to assign the value to
     * @param mixed  The value to set
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->args['custom'][] = $value;
        } else {
            $this->args['custom'][$offset] = $value;
        }
    }

    /**
     * Whether or not an offset exists.
     *
     * @param string An offset to check for
     *
     * @return bool
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->args['custom']);
    }

    /**
     * Unsets an offset.
     *
     * @param string The offset to unset
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset)
    {
        unset($this->args['custom'][$offset]);
    }

    /**
     * Returns the value at specified offset.
     *
     * @param string The offset to retrieve
     *
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->args['custom'][$offset];
    }

    public function expr($properties = [], $arguments = null)
    {
        // If we use DSQL Connection, then we should call expr() from there.
        // Connection->expr() will return correct, connection specific Expression class.
        if ($this->connection instanceof Connection) {
            return $this->connection->expr($properties, $arguments);
        }

        // Otherwise, connection is probably PDO and we don't know which Expression
        // class to use, so we make a smart guess :)
        if ($this instanceof Query) {
            $e = new self($properties, $arguments);
        } else {
            $e = new static($properties, $arguments);
        }

        $e->escape_char = $this->escape_char;
        $e->connection = $this->connection;

        return $e;
    }

    public function reset($tag = null)
    {
        // unset all arguments
        if ($tag === null) {
            $this->args = ['custom' => []];

            return $this;
        }

        if (!is_string($tag)) {
            throw new ModelException(
                '$tag 가 문자열이여야 합니다.',
                500,
                $tag
            );
        }

        // unset custom/argument or argument if such exists
        if ($this->offsetExists($tag)) {
            $this->offsetUnset($tag);
        } elseif (isset($this->args[$tag])) {
            unset($this->args[$tag]);
        }

        return $this;
    }

    protected function _consume($sql_code, $escape_mode = 'param')
    {
        if (!is_object($sql_code)) {
            switch ($escape_mode) {
                case 'param':
                    return $this->_param($sql_code);
                case 'escape':
                    return $this->_escape($sql_code);
                case 'soft-escape':
                    return $this->_escapeSoft($sql_code);
                case 'none':
                    return $sql_code;
            }

            throw new ModelException(
                '$escape_mode 값이 없습니다. driver 또는 클래스를 확인하세요.',
                500,
                $escape_mode
            );
        }

        // User may add Expressionable trait to any class, then pass it's objects
        if ($sql_code instanceof Expressionable) {
            $sql_code = $sql_code->getDSQLExpression($this);
        }

        if (!$sql_code instanceof self) {
            throw new ModelException(
                'Expression object 가 Expression instance 가 아닙니다.',
                500,
                $sql_code
            );
        }

        // at this point $sql_code is instance of Expression
        $sql_code->params = &$this->params;
        $sql_code->_paramBase = &$this->_paramBase;
        $ret = $sql_code->render();

        // Queries should be wrapped in parentheses in most cases
        if ($sql_code instanceof Query) {
            $ret = '('.$ret.')';
        }

        // unset is needed here because ->params=&$othervar->params=foo will also change $othervar.
        // if we unset() first, we’re safe.
        unset($sql_code->params);
        $sql_code->params = [];

        return $ret;
    }

    /**
     * Given the string parameter, it will detect some "deal-breaker" for our
     * soft escaping, such as "*" or "(".
     * Those will typically indicate that expression is passed and shouldn't
     * be escaped.
     */
    protected function isUnescapablePattern($value)
    {
        return is_object($value)
            || $value === '*'
            || strpos($value, '(') !== false
            || strpos($value, $this->escape_char) !== false;
    }

    /**
     * Soft-escaping SQL identifier. This method will attempt to put
     * escaping char around the identifier, however will not do so if you
     * are using special characters like ".", "(" or escaping char.
     *
     * It will smartly escape table.field type of strings resulting
     * in "table"."field".
     *
     * @param mixed $value Any string or array of strings
     *
     * @return string|array Escaped string or array of strings
     */
    protected function _escapeSoft($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        // in some cases we should not escape
        if ($this->isUnescapablePattern($value)) {
            return $value;
        }

        if (is_string($value) && strpos($value, '.') !== false) {
            return implode('.', array_map(__METHOD__, explode('.', $value)));
        }

        return $this->escape_char.trim($value).$this->escape_char;
    }

    /**
     * Creates new expression where $sql_code appears escaped. Use this
     * method as a conventional means of specifying arguments when you
     * think they might have a nasty back-ticks or commas in the field
     * names.
     *
     * @param string $value
     *
     * @return string
     */
    public function escape($value)
    {
        return $this->expr('{}', [$value]);
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field
     * names such as "table" as well as other characters that SQL
     * permits in the identifiers (e.g. spaces or equation signs).
     *
     * @param mixed $value Any string or array of strings
     *
     * @return string|array Escaped string or array of strings
     */
    protected function _escape($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        // in all other cases we should escape
        return
            $this->escape_char
            .str_replace($this->escape_char, $this->escape_char.$this->escape_char, $value)
            .$this->escape_char;
    }

    /**
     * Converts value into parameter and returns reference. Use only during
     * query rendering. Consider using `_consume()` instead, which will
     * also handle nested expressions properly.
     *
     * @param string|array $value String literal or array of strings containing input data
     *
     * @return string|array Name of parameter or array of names
     */
    protected function _param($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        $name = ':'.$this->_paramBase;
        $this->_paramBase++;
        $this->params[$name] = $value;

        return $name;
    }

    /**
     * Render expression and return it as string.
     *
     * @return string Rendered query
     */
    public function render()
    {
        $nameless_count = 0;
        if (!isset($this->_paramBase)) {
            $this->_paramBase = $this->paramBase;
        }

        if ($this->template === null) {
            throw new ModelException(
                'Template 에서 초기화된 Expression 을 찾을수 없습니다.',
                500
            );
        }

        $res = preg_replace_callback(
            '/\[[a-z0-9_]*\]|{[a-z0-9_]*}/i',
            function ($matches) use (&$nameless_count) {
                $identifier = substr($matches[0], 1, -1);
                $escaping = ($matches[0][0] == '[') ? 'param' : 'escape';

                // Allow template to contain []
                if ($identifier === '') {
                    $identifier = $nameless_count++;

                    // use rendering only with named tags
                }
                $fx = '_render_'.$identifier;

                // [foo] will attempt to call $this->_render_foo()

                if (array_key_exists($identifier, $this->args['custom'])) {
                    $value = $this->_consume($this->args['custom'][$identifier], $escaping);
                } elseif (method_exists($this, $fx)) {
                    $value = $this->$fx();
                } else {
                    throw new ModelException(
                        'Expression tag에 대한 쿼리를 생성할수 없습니다.',
                        500,
                        $identifier
                    );
                }

                return is_array($value) ? '('.implode(',', $value).')' : $value;
            },
            $this->template
        );
        unset($this->_paramBase);

        return trim($res);
    }

    /**
     * Return formatted debug output.
     *
     * Ignore false positive warnings of PHPMD.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param bool $html Show as HTML?
     *
     * @return string SQL syntax of query
     */
    public function getDebugQuery($html = null)
    {
        $d = $this->render();
        $pp = [];
        foreach (array_reverse($this->params) as $key => $val) {
            if (is_numeric($val)) {
                $d = preg_replace(
                    '/'.$key.'([^_]|$)/',
                    $val.'\1',
                    $d
                );
            } elseif (is_string($val)) {
                $d = preg_replace('/'.$key.'([^_]|$)/', "'".addslashes($val)."'\\1", $d);
            } elseif ($val === null) {
                $d = preg_replace(
                    '/'.$key.'([^_]|$)/',
                    'NULL\1',
                    $d
                );
            } else {
                $d = preg_replace('/'.$key.'([^_]|$)/', $val.'\\1', $d);
            }
            $pp[] = $key;
        }
        if (class_exists('SqlFormatter')) {
            if ($html) {
                $result = \SqlFormatter::format($d);
            } else {
                $result = \SqlFormatter::format($d, false);
            }
        } else {
            $result = $d;  // output as-is
        }
        if (!$html) {
            return str_replace('#lte#', '<=', strip_tags(str_replace('<=', '#lte#', $result), '<>'));
        }

        return $result;
    }

    public function __debugInfo()
    {
        $arr = [
            'R'          => false,
            'template'   => $this->template,
            'params'     => $this->params,
//            'connection' => $this->connection,
            'args'       => $this->args,
        ];

        try {
            $arr['R'] = $this->getDebugQuery();
        } catch (\Exception $e) {
            $arr['R'] = $e->getMessage();
        }

        return $arr;
    }

    public function execute($connection = null)
    {
        if ($connection === null) {
            if ($this->mode == 'select' && is_object($this->slaveConnection)) {
                $this->type = 'slave';
                $connection = $this->slaveConnection;
            } else {
                $connection = $this->connection;
            }
        }

        // If it's a PDO connection, we're cool
        if ($connection instanceof \PDO) {
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // We support PDO
            $query = $this->render();
            $statement = $connection->prepare($query);
            foreach ($this->params as $key => $val) {
                if (is_int($val)) {
                    $type = \PDO::PARAM_INT;
                } elseif (is_bool($val)) {
                    // SQL does not like booleans at all, so convert them INT
                    $type = \PDO::PARAM_INT;
                    $val = (int) $val;
                } elseif ($val === null) {
                    $type = \PDO::PARAM_NULL;
                } elseif (is_string($val) || is_float($val)) {
                    $type = \PDO::PARAM_STR;
                } elseif (is_resource($val)) {
                    $type = \PDO::PARAM_LOB;
                } else {
                    throw new Exception([
                        'Incorrect param type',
                        'key'   => $key,
                        'value' => $val,
                        'type'  => gettype($val),
                    ]);
                }

                // Workaround to support LOB data type. See https://github.com/doctrine/dbal/pull/2434
                $this->boundValues[$key] = $val;
                if ($type === \PDO::PARAM_STR) {
                    $bind = $statement->bindParam($key, $this->boundValues[$key], $type, strlen($val));
                } else {
                    $bind = $statement->bindParam($key, $this->boundValues[$key], $type);
                }

                if (!$bind) {
                    throw new ModelException(
                        'parameter 를 확인하세요.',
                        500,
                        "param:{$key}, value:{$val}, type:{$type}"
                    );
                }
            }

            $statement->setFetchMode(\PDO::FETCH_ASSOC);

            try {
                $statement->execute();
            } catch (Exception $e) {
                $exception = new ModelException(
                    "쿼리실행중 문제가 발생했습니다.",
                    500,
                    $this->getDebugQuery()
                );
            }

            return $statement;
        } else {
            return $connection->execute($this);
        }
    }

    public function getIterator()
    {
        return $this->execute();
    }

    public function get()
    {
        $stmt = $this->execute();

        if ($stmt instanceof \Generator) {
            return iterator_to_array($stmt);
        }

        return $stmt->fetchAll();
    }

    public function getOne()
    {
        $data = $this->getRow();
        if (!$data) {
            throw new ModelException(
                '해당 쿼리에서 단일 ROW를 가져올 수 없습니다.',
                500,
                $this->getDebugQuery()
            );
        }
        $one = array_shift($data);

        return $one;
    }

    public function getRow()
    {
        $stmt = $this->execute();

        if ($stmt instanceof \Generator) {
            return $stmt->current();
        }

        return $stmt->fetch();
    }
}
