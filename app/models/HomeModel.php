<?php

/**
 * Class HomeModel
 */
class HomeModel extends Model
{
    public function find()
    {
        $collection = $this->connect->mongodb_tutorial->users;

        return $collection->find()->toArray();
    }

    public function createCollection($collection)
    {

    }
}