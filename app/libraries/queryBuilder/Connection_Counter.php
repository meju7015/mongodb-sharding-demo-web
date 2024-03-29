<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오후 1:51
 */
class Connection_Counter extends Connection_Proxy
{
    public $callback = null;
    protected $selects = 0;
    protected $queries = 0;
    protected $expressions = 0;
    protected $rows = 0;

    public function iterate($ret)
    {
        foreach ($ret as $key => $row) {
            $this->rows++;
            //yield $key => $row;
        }
    }

    public function execute(Expression $expr)
    {
        if ($expr instanceof Query) {
            $this->queries++;
            if ($expr->mode === 'select' || $expr->mode === null) {
                $this->selects++;
            }
        } else {
            $this->expressions++;
        }

        try {
            $ret = parent::execute($expr);
        } catch (Exception $e) {
            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $this->queries, $this->selects, $this->rows, $this->expressions, true);
            } else {
                printf(
                    "[ERROR] Queries: %3d, Selects: %3d, Rows fetched: %4d, Expressions %3d\n",
                    $this->queries,
                    $this->selects,
                    $this->rows,
                    $this->expressions
                );
            }

            throw $e;
        }

        return $this->iterate($ret);
    }

    public function __destruct()
    {
        if ($this->callback && is_callable($this->callback)) {
            call_user_func($this->callback, $this->queries, $this->selects, $this->rows, $this->expressions, false);
        } else {
            printf(
                "Queries: %3d, Selects: %3d, Rows fetched: %4d, Expressions %3d\n",
                $this->queries,
                $this->selects,
                $this->rows,
                $this->expressions
            );
        }
    }
}