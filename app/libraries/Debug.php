<?php
/**
 * Class Debug
 */
class Debug
{
    public static $trace;

    public static function store($data, $key = null)
    {
        if ($key !== null) {
            self::$trace[$key] = $data;
        } else {
            self::$trace[] = $data;
        }

        return new static;
    }

    public static function pop()
    {
        return self::$trace;
    }

    public static function display($data = null)
    {
        if ($data) {
            self::$trace = $data;
        }

        if (!Config::$isCommand) {
            echo '<pre style="position:relative; width:100%; top:0; height: 300px; padding:10px; background-color:#282828; color:greenyellow; z-index: 9999; overflow: auto;">';
            print_r(self::$trace);
            echo '</pre>';
        } else {
            print_r(self::$trace);
            echo PHP_EOL;
        }
    }
}