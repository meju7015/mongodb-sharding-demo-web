<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-09-19
 * Time: 오후 5:49
 */
class BootStrap
{
    protected $rootDir;

    public function __construct()
    {
        $this->rootDir = Config::getRootDir();
    }

    private function autoLoad()
    {
        /**
         * do not change index
         */
        $path = Array(
            "app/interface/",
            "app/exceptions/",
            "app/core/",
            "app/libraries/",
            "app/controllers/",
            "app/models/",
            "routes/"
        );

        foreach ($path as $key => $item) {
            $loader = array_diff(scandir($this->rootDir.$item), Array('.', '..'));
            foreach ($loader as $file) {
                if (strpos($file, '.php') === false || $file[0] === '_') {
                    continue;
                }

                $filePath = $this->rootDir.$item.$file;

                include_once $filePath;
            }
        }

        if (!Router::match()) {
            throw new RouteException('페이지를 찾을수 없습니다.', 405);
        }

        return $this;
    }

    public function wakeUp()
    {
        $this->autoLoad();
    }
}