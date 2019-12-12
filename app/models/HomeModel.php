<?php

/**
 * Class HomeModel
 */
class HomeModel extends Model
{
    public function find($request)
    {
        $command = $request['params'];
        $collection = $this->connect->mongodb_tutorial->{$request['collection']};
        return $collection->find()->toArray();
    }

    public function createCollection($collection)
    {

    }

    public function query($query)
    {
        return print_r($this->connect->db->command($query));
    }
}