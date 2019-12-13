<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-25
 * Time: 오전 10:30
 */
interface ResultSet
{
    public function get();
    public function getRow();
    public function getOne();
}