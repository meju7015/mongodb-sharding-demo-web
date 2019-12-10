<?php

class Env
{
    public static $env;

    private static $envPath;

    public static function get($name)
    {
        return self::$env[$name];
    }

    public static function getDBConnectInfo()
    {
        self::$envPath = Config::getRootDir() . ".env";

        if (file_exists(self::$envPath)) {

            if (!self::$env) {
                $env = json_decode(file_get_contents(self::$envPath), true);
                self::$env = $env;
            }

            if (!self::$env) {
                throw new ModelException('ENV 파일이 잘못되었습니다.', 405);
            }

            $driver = self::$env['driver'];

            if (!isset(self::$env[$driver])) {
                return Array(
                    'master' => Array(
                        'dns' => $driver.':host=localhost',
                        'username' => 'root',
                        'password' => 'root',
                        'options' => null
                    ),
                    'slave' => Array(
                        'dns' => $driver.':host=localhost',
                        'username' => 'root',
                        'password' => 'root',
                        'options' => null
                    ),
                );
            }

            $databaseInfo = self::$env[$driver];

            return Array(
                'master' => Array(
                    'dns' => $driver.':host='.$databaseInfo['master_dns'],
                    'username' => $databaseInfo['master_username'],
                    'password' => $databaseInfo['master_password'],
                    'options' => $databaseInfo['master_options']
                ),
                'slave' => Array(
                    'dns' => $driver.':host='.$databaseInfo['slave_dns'],
                    'username' => $databaseInfo['slave_username'],
                    'password' => $databaseInfo['slave_password'],
                    'options' => $databaseInfo['slave_options']
                )
            );

        } else {
            throw new ModelException('ENV 파일을 찾을수 없습니다.', 404);
        }
    }
}