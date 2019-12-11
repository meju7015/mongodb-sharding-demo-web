<?php
/**
 * Env 클래스
 */
class Env
{
    public static $env;

    private static $envPath;

    public static function set()
    {
        if (!self::$env) {
            self::$envPath = Config::getRootDir() . ".env";

            if (!file_exists(self::$envPath)) {
                throw new RouteException('ENV 파일이 없습니다.', 405);
            }

            $env = json_decode(file_get_contents(self::$envPath), true);
            self::$env = $env;
        }

        if (!self::$env) {
            throw new RouteException('ENV 파일이 잘못되었습니다.', 405);
        }
    }

    public static function get($name)
    {
        return self::$env[$name];
    }

    public static function getDBConnectInfo()
    {
        if (!self::$env) {
            self::set();
        }

        $driver = self::$env['driver'];

        if (!isset(self::$env[$driver])) {
            return Array(
                'master' => Array(
                    'dsn' => $driver . ':host=localhost',
                    'username' => 'root',
                    'password' => 'root',
                    'options' => null
                ),
                'slave' => Array(
                    'dsn' => $driver . ':host=localhost',
                    'username' => 'root',
                    'password' => 'root',
                    'options' => null
                ),
            );
        }

        $databaseInfo = self::$env[$driver];

        switch ($driver) {
            case 'mysql':
                return [
                    'master' => [
                        'dsn' =>
                            $driver .
                            ':host=' . $databaseInfo['master_dsn'] .
                            ';port=' . $databaseInfo['master_port'] .
                            (isset($databaseInfo['master_database']) ? ';db=' . $databaseInfo['master_database'] : ''),
                        'username' => $databaseInfo['master_username'],
                        'password' => $databaseInfo['master_password'],
                        'options' => $databaseInfo['master_options']
                    ],
                    'slave' => [
                        'dsn' =>
                            $driver .
                            ':host=' . $databaseInfo['slave_dsn'] .
                            ';port=' . $databaseInfo['slave_port'] .
                            (isset($databaseInfo['slave_database']) ? ';db=' . $databaseInfo['slave_database'] : ''),
                        'username' => $databaseInfo['slave_username'],
                        'password' => $databaseInfo['slave_password'],
                        'options' => $databaseInfo['slave_options']
                    ]
                ];
            case 'mongodb':
                return [
                    'master' => "mongodb://{$databaseInfo['master_username']}.{$databaseInfo['master_password']}@{$databaseInfo['master_dsn']}:{$databaseInfo['master_port']}/test"
                ];
        }


    }
}