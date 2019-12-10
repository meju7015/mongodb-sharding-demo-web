<?php

class Router
{
    /**
     * @var array 라우터 저장 배열 (incl. 정의된 라우터).
     */
    protected static $routes = Array();

    /**
     * @var array 라우터 저장 이름
     */
    protected static $namedRoutes = Array();

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected static $basePath = '';

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected static $matchTypes = Array(
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    );

    /*public function __construct(Array $routes = Array(), $basePath = '', Array $matchTypes = Array())
    {
        $this->addRoutes($routes);
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }*/

    public static function getRoutes()
    {
        return self::$routes;
    }

    public static function addRoutes($routes)
    {
        if (!is_array($routes) && !$routes instanceof Traversable)
        {
            throw new Exception('Routes should an array or an instancce of Traversable');
        }

        foreach ($routes as $route) {
            call_user_func_array(Array('static', 'map'), $route);
        }
    }

    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }

    public static function addMatchTypes($matchTypes)
    {
        self::$matchTypes = array_merge(self::$matchTypes, $matchTypes);
    }

    public static function map($method, $route, $target, $name = null)
    {
        self::$routes[] = Array($method, $route, $target, $name);

        if ($name) {
            if (isset(self::$namedRoutes[$name])) {
                throw new Exception("Can not redeclare route '{$name}'");
            } else {
                self::$namedRoutes[$name] = $route;
            }
        }

        return;
    }

    public static function get($route, $target, $name = null)
    {
        self::map('get', $route, $target, $name);
    }

    public static function post($route, $target, $name = null)
    {
        self::map('post', $route, $target, $name);

        /*$match = self::match();

        if (is_array($match)) {
            $split = explode('.', $match['target']);

            list($className, $methodName) = $split;

            if (class_exists($className)) {
                $controller = new $className;
                if (method_exists($controller, $methodName)) {
                    call_user_func_array($controller->{$methodName}(), $match['params']);
                } else {
                    throw new RouteException('페이지를 찾을수 없습니다.', 405, 'Not found method');
                }
            } else {
                throw new RouteException('페이지를 찾을수 없습니다.', 405, 'Not found class');
            }
        }*/
    }

    public static function generate($routeName, Array $params = Array())
    {
        if (!isset(self::$namedRoutes[$routeName])) {
            throw new Exception("Route '{$routeName}' does not exist.");
        }

        $route = self::$namedRoutes[$routeName];

        $url = self::$basePath . $route;

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if ($pre) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    $url = str_replace($block, $params[$param], $url);
                } elseif ($optional) {
                    $url = str_replace($pre . $block, '', $url);
                }
            }
        }

        return $url;
    }

    public static function match($requestUrl = null, $requestMethod = null)
    {
        $params = Array();
        $match = false;

        if ($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        }

        if (Config::IS_SUBDIRECTORY) {
            $requestUrl = str_replace(Config::DEFAULT_SITE, '', $requestUrl);
        }

        $requestUrl = substr($requestUrl, strlen(self::$basePath));

        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        if ($requestMethod === null) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }

        foreach (self::$routes as $handler) {
            list($method, $_route, $target, $name) = $handler;

            $methods = explode('|', $method);
            $method_match = false;

            foreach ($methods as $method) {
                if (strcasecmp($requestMethod, $method) === 0) {
                    $method_match = true;
                    break;
                }
            }

            if (!$method_match) continue;

            if ($_route === '*') {
                $match = true;
            } elseif (isset($_route[0]) && $_route[0] === '@') {
                $pattern = '`' . substr($_route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params);
            } else {
                $route = null;
                $regex = false;
                $j = 0;
                $n = isset($_route[0]) ? $_route[0] : null;
                $i = 0;

                while (true) {
                    if (!isset($_route[$i])) {
                        break;
                    } elseif (false === $regex) {
                        $c = $n;
                        $regex = $c === '[' || $c === '(' || $c === '.';
                        if (false === $regex && false !== isset($_route[$i+1])) {
                            $n = $_route[$i+1];
                            $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                        }

                        if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
                            continue 2;
                        }

                        $j++;
                    }

                    $route .= $_route[$i++];
                }

                $regex = self::compileRoute($route);
                $match = preg_match($regex, $requestUrl, $params);
            }

            if (($match === true || $match > 0)) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) unset($params[$key]);
                    }
                }

                $split = explode('.', $target);

                if (class_exists($split[0])) {
                    $controller = new $split[0];
                    if (method_exists($controller, $split[1])) {
                        call_user_func_array($controller->{$split[1]}($GLOBALS['_'.$requestMethod], $params), null);

                        return new static;
                    }
                }
            }
        }

        return false;
    }

    private static function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = self::$matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }

                if ($pre === '.') {
                    $pre = '\.';
                }

                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }
}