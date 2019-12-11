<?php
/**
 * Router 클래스
 */
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
     * @var array 라우터 그룹
     */
    protected static $groupRoute = Array();

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected static $basePath = '';

    /**
     * @var string 마지막 저장된 라우터
     */
    private static $lastRoute = '';

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

    /**
     * Redirect function
     *
     * @param $uri
     */
    public static function follow($uri)
    {
        header('Location:'.$uri);
        exit;
    }

    /**
     * 모든 라우터리스트를 반환
     *
     * @return array
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     * 그룹별 라우터를 선언해줄수 있습니다.
     * 그룹별로 미틀웨어를 설정할수 있습니다
     *
     * @param $routes
     * @return Router
     * @throws Exception
     */
    public static function addRoutes($routes)
    {
        if (!is_array($routes) && !$routes instanceof \Traversable) {
            throw new Exception('Routes should an array or an instancce of Traversable');
        }

        self::$groupRoute[] = $routes;

        foreach ($routes as $route) {
            call_user_func_array(Array('static', 'map'), $route);
        }

        return new self;
    }

    /**
     * 라우트 경로 저장
     *
     * @param $basePath
     */
    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }

    /**
     * 커스텀 매치 타입 생성
     *
     * @param $matchTypes
     */
    public static function addMatchTypes($matchTypes)
    {
        self::$matchTypes = array_merge(self::$matchTypes, $matchTypes);
    }

    /**
     * 라우터 정의
     *
     * @param $method
     * @param $route
     * @param $target
     * @param null $name
     * @param null $middleware
     * @throws Exception
     */
    public static function map($method, $route, $target, $name = null, $middleware = null)
    {
        $route = Array($method, $route, $target, $name);

        if ($middleware !== null) {
            array_push($route, $middleware);
        }

        self::$routes[] = $route;

        if ($name) {
            if (isset(self::$namedRoutes[$name])) {
                throw new Exception("Can not redeclare route '{$name}'");
            } else {
                self::$namedRoutes[$name] = $route;
            }
        }

        return;
    }

    /**
     * 미들웨어 정의
     *
     * @param $target
     * @throws RouteException
     */
    public static function middleware($target)
    {
        if (empty($target)) {
            throw new RouteException(
                '$target is empty',
                500,
                $target
            );
        }

        list($class, $function) = explode('.', $target);

        if (class_exists($class)) {
            $object = new $class;
            if (method_exists($object, $function)) {
                foreach (self::$routes as $key => $route) {
                    list(, $routeName) = $route;

                    if ($groupRoute = end(self::$groupRoute)) {
                        foreach ($groupRoute as $_route) {
                            list(, $_routeName) = $_route;

                            if ($routeName === $_routeName) {
                                array_push(
                                    self::$routes[$key], $target
                                );
                            }
                        }
                    } elseif ($routeName === self::$lastRoute) {
                        array_push(
                            self::$routes[$key], $target
                        );
                    }
                }

                self::$groupRoute = [];
            }
        }
    }

    /**
     * GET Method 라우터 정의
     *
     * @param $route
     * @param $target
     * @param null $name
     * @param null $middleware
     * @return Router
     * @throws Exception
     */
    public static function get($route, $target, $name = null, $middleware = null)
    {
        self::map('get', $route, $target, $name, $middleware);
        self::$lastRoute = $route;

        return new self;
    }

    /**
     * POST Method 라우터 정의
     *
     * @param $route
     * @param $target
     * @param null $name
     * @param null $middleware
     * @return Router
     * @throws Exception
     */
    public static function post($route, $target, $name = null, $middleware = null)
    {
        self::map('post', $route, $target, $name, $middleware);
        self::$lastRoute = $route;

        return new self;
    }

    /**
     * Command 라우터 정의
     *
     * @param $route
     * @param $target
     * @param null $name
     * @param null $middleware
     * @return Router
     * @throws Exception
     */
    public static function command($route, $target, $name = null, $middleware = null)
    {
        self::map(METHOD_COMMAND, $route, $target, $name, $middleware);
        self::$lastRoute = $route;

        return new self;
    }

    /**
     * Request URI 에서 라우투 Generate
     *
     * @param $routeName
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
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

    /**
     * Request URI 에서 매치되는 라우터 타겟 및 미들웨어 Call
     *
     * @param null $requestUrl
     * @param null $requestMethod
     * @return bool|Router
     */
    public static function match($requestUrl = null, $requestMethod = null)
    {
        $params = Array();
        $match = false;

        Debug::store(['Routes' => Router::getRoutes()]);

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
            list($method, $_route, $target, $name, $middleware) = $handler;

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
                        if ($middleware) {
                            list($_middlewareClass, $_middlewareFunc) = explode('.', $middleware);
                            $middlewareClass = new $_middlewareClass;

                            if (!$middlewareClass->$_middlewareFunc($handler, $_SESSION)) {
                                // TODO :: Exception Middleware;
                                return false;
                            }
                        }

                        if (Config::$isCommand) {
                            call_user_func_array($controller->{$split[1]}($_SERVER['argv'], $params), null);
                        } else {
                            call_user_func_array($controller->{$split[1]}($GLOBALS['_'.$requestMethod], $params), null);
                        }
                        return new static;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 라우터 매치타입 컴파일
     *
     * @param $route
     * @return string
     */
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