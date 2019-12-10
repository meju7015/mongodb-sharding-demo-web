<?php
class DBConfig
{
    public static $DATABASE_INFO = '';

    /*public static $PRODUCT_INFO = Array(
        'master'  => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        ),
        'slave' => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        )
    );

    public static $DEVELOP_INFO = Array(
        'master'  => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        ),
        'slave' => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        )
    );

    public static $QA_INFO = Array(
        'master'  => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        ),
        'slave' => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        )
    );

    public static $LOCAL_INFO = Array(
        'master'  => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        ),
        'slave' => Array(
            'dsn'  => 'mysql:host=localhost',
            'userName'  => 'root',
            'password'  => '1234',
            'options'   => null
        )
    );*/

    public static function setDatabaseInfo($info = null)
    {
        self::$DATABASE_INFO = $info;

        /*switch ($chanel) {
            case PRODUCT_CHANEL:
                self::$DATABASE_INFO = self::$PRODUCT_INFO;
                break;
            case DEVELOP_CHANEL:
                self::$DATABASE_INFO = self::$DEVELOP_INFO;
                break;
            case LOCAL_CHANEL:
                self::$DATABASE_INFO = self::$LOCAL_INFO;
                break;
            default:
                self::$DATABASE_INFO = self::$PRODUCT_INFO;
        }*/
    }

    public static function getDatabaseInfo()
    {
        return self::$DATABASE_INFO;
    }
}