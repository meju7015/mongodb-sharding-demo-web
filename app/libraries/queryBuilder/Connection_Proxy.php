<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오후 1:48
 */
class Connection_Proxy extends Connection
{
    public function __construct($properties = Array())
    {
        parent::__construct($properties);

        if ($this->connection instanceof Connection && $this->connection->driver) {
            $this->driver = $this->connection->driver;
        }
    }

    public function connection()
    {
        return $this->connection->connection();
    }

    public function dsql($properties = Array())
    {
        $dsql = $this->connection->dsql($properties);
        $dsql->connection = $this;

        return $dsql;
    }

    public function expr($properties = Array(), $arguments = null)
    {
        $expr = $this->connection->expr($properties, $arguments);
        $expr->connection = $this;

        return $expr;
    }

    public function execute(Expression $expr)
    {
        return $this->connection->execute($expr);
    }
}