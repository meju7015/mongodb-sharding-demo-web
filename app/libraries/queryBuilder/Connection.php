<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오후 1:23
 */
class Connection
{
    protected $query_class = 'Query';
    protected $expression_class = 'Expression';
    protected $connection = null;
    public $transaction_depth = 0;
    public $driver;

    public function __construct($properties = Array())
    {
        if (!is_array($properties)) {
            throw new Exception(Array(
                'Invalid properties for "new Connection()". Did you mean to call Connection::connect()?',
                'properties' => $properties
            ));
        }

        $this->setDefaults($properties);
    }

    public function setDefaults($properties = Array(), $passively = false)
    {
        if ($properties === null) {
            $properties = Array();
        }

        foreach ($properties as $key => $val) {
            if (!is_numeric($key) && property_exists($this, $key)) {
                if ($passively && $this->$key !== null) {
                    continue;
                }

                if ($val !== null) {
                    $this->$key = $val;
                }
            } else {
                $this->setMissingProperty($key, $val);
            }
        }
    }

    protected function setMissingProperty($key, $value)
    {
        if (is_numeric($key)) {
            return;
        }

        throw new Exception(Array(
            'Property for specified object is not defined',
            'object'    => $this,
            'property'  => $key,
            'value'     => $value
        ));
    }

    public static function normalizeDSN($dsn, $user = null, $pass = null)
    {
        $parts = is_array($dsn) ? $dsn : parse_url($dsn);

        if ($parts !== false && isset($parts['host'], $part['path'])) {
            $dsn =
                $parts['scheme'].
                ':host='.$parts['host'].
                (isset($parts['port']) ? ';port='.$parts['port'] : '').
                ';dbname='.substr($parts['path'], 1);
            $user = $user !== null ? $user : (isset($parts['user']) ? $parts['user'] : null);
            $pass = $pass !== null ? $pass : (isset($parts['pass']) ? $parts['pass'] : null);
        }

        if (is_array($dsn)) {
            return $dsn;
        }

        if (is_string($dsn)) {
            if (strpos($dsn, ':') === false) {
                throw new Exception(Array(
                    "Your DSN format is invalid. Must be in 'driver:host;options' format",
                    'dsn' => $dsn
                ));
            }
            list($driver, $rest) = explode(':', $dsn, 2);
            $driver = strtolower($driver);
        } else {
            $driver = $rest = null;
        }

        return Array(
            'dsn' => $dsn,
            'user' => $user,
            'pass' => $pass,
            'driver' => $driver,
            'rest' => $rest
        );
    }

    public static function connect($dsn, $user = null, $password = null, $args = Array())
    {
        if ($dsn instanceof PDO) {
            $driver = $dsn->getAttribute(PDO::ATTR_DRIVER_NAME);
            $connectionClass = 'Connection';
            $queryClass = null;
            $expressionClass = null;
            switch ($driver) {
                case 'pgsql':
                    $connectionClass = 'Connection_PgSQL';
                    $queryClass = 'Query_PgSQL';
                    break;
                case 'oci':
                    $connectionClass = 'Connection_Oracle';
                    break;
                case 'sqlite':
                    $queryClass = 'Query_SQLite';
                    break;
                case 'mysql':
                    $expressionClass = 'Expression_MySQL';
                default:
                    $queryClass = 'Query_MySQL';
                    break;
            }

            return new $connectionClass(array_merge(Array(
                'connection'        => $dsn,
                'query_class'       => $queryClass,
                'expression_class'  => $expressionClass,
                'driver'            => $driver
            ), $args));
        }

        if (is_object($dsn)) {
            return new Connection_Proxy(array_merge(Array(
                'connection'        => $dsn
            ), $args));
        }

        $dsn = static::normalizeDSN($dsn, $user, $password);

        switch ($dsn['driver']) {
            case 'mysql':
                $c = new static(array_merge(Array(
                    'connection'        => new PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                    'expression_class'  => 'Expression_MySQL',
                    'query_class'       => 'Query_MySQL',
                    'driver'            => $dsn['driver']
                ), $args));
                break;

            case 'sqlite':
                $c = new static(array_merge(Array(
                    'connection'        => new PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                    'query_class'       => 'Query_SQLite',
                    'driver'            => $dsn['driver']
                ), $args));
                break;

            case 'pgsql':
                $c = new Connection_PgSQL(array_merge(Array(
                    'connection'    => new PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                    'driver'        => $dsn['driver']
                ), $args));
                break;

            case 'dumper':
                $c = new Connection_Dumper(array_merge(Array(
                    'connection' => static::connect($dsn['rest'], $dsn['user'], $dsn['pass'])
                ), $args));
                break;

            case 'counter':
                $c = new Connection_Counter(array_merge(Array(
                    'connection' => static::connect($dsn['rest'], $dsn['user'], $dsn['pass'])
                ), $args));
                break;

            default:
                $c = new static(array_merge(Array(
                    'connection' => static::connect(new PDO($dsn['dsn'], $dsn['user'], $dsn['pass']))
                ), $args));
        }

        return $c;
    }

    public function dsql($properties = Array())
    {
        $c = $this->query_class;
        $q = new $c($properties);
        $q->connection = $this;

        return $q;
    }

    public function expr($properties = Array(), $arguments= null)
    {
        $c = $this->expression_class;
        $e = new $c($properties, $arguments);
        $e->connection = $this->connection ?: $this;

        return $e;
    }

    public function connection()
    {
        return $this->connection;
    }

    public function execute(Expression $expr)
    {
        if ($this->connection && $this->connection !== $this) {
            return $expr->execute($this->connection);
        }

        throw new Exception('Queries cannot be executed through this connection');
    }

    public function atomic($f)
    {
        $this->beginTransaction();

        try {
            $res = call_user_func($f);
            $this->commit();

            return $res;
        } catch (Exception $e) {
            $this->rollBack();

            throw $e;
        }
    }

    public function beginTransaction()
    {
        $r = $this->inTransaction()
            ? false
            : $this->connection->beginTransaction();

        $this->transaction_depth++;

        return $r;
    }

    public function inTransaction()
    {
        return $this->transaction_depth > 0;
    }

    public function commit()
    {
        if (!$this->inTransaction()) {
            throw new Exception('Using commit() when no transaction has started');
        }

        $this->transaction_depth--;

        if ($this->transaction_depth == 0) {
            return $this->connection->commit();
        }

        return false;
    }

    public function rollBack()
    {
        if (!$this->inTransaction()) {
            throw new Exception('Using rollBack() when no transaction has started');
        }

        $this->transaction_depth--;

        if ($this->transaction_depth == 0) {
            return $this->connection->rollBack();
        }

        return false;
    }

    public function lastInsertID($m = null)
    {
        return $this->connection()->lastInsertID();
    }
}