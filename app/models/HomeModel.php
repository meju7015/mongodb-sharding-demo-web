<?php

/**
 * Class HomeModel
 */
class HomeModel extends Model
{
    public function selectUsers($userID)
    {

    }

    public function find()
    {
        $collection = $this->connect->test->TestCollection;

        return $collection->find();
    }
}