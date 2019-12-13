<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오후 1:57
 */
class Connection_Dumper extends Connection_Proxy
{
    public $callback = null;
    protected $start_time;
    public function execute(Expression $expr)
    {
        $this->start_time = microtime(true);

        try {
            $ret = parent::execute($expr);
            $took = microtime(true) - $this->start_time;

            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $expr, $took, false);
            } else {
                printf("[%02.6f] %s\n", $took, $expr->getDebugQuery());
            }
        } catch (Exception $e) {
            $took = microtime(true) - $this->start_time;

            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $expr, $took, true);
            } else {
                printf("[ERROR %02.6f] %s\n", $took, $expr->getDebugQuery());
            }

            throw $e;
        }

        return $ret;
    }
}