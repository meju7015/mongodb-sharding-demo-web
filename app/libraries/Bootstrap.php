<?php
/**
 * Bootstrap
 * app 실행시 첫 진입점 입니다.
 * 각종 Core 파일과 Library 파일을 로딩할수 있습니다.
 *
 * User: mason
 * Date: 2019-09-19
 * Time: 오후 5:49
 */
class BootStrap
{
    /**
     * @var string public 상위 디렉토리 입니다. /var/html/uframework
     */
    protected $rootDir;

    public function __construct()
    {
        $this->rootDir = Config::getRootDir();
    }

    /**
     * 이 함수를 이용해 독립된 라이브러리 모듈을 로딩할수 있습니다.
     *
     * @param $dir
     * @param array $include
     */
    private function callLibararry($dir, $include = Array())
    {
        foreach ($include as $path) {

            $path = $dir.$path.'.php';
            if (file_exists($path)) {

                include_once $path;
            }
        }
    }

    /**
     * Core 파일 로딩
     *
     * @throws RouteException
     */
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
            "app/middleware/",
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

        /**
         * 코어 로딩 완료 후 작업
         */
        Env::set();


        if (Config::DEBUG) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ERROR);
        }

        $this->callLibararry(
            $this->rootDir."app/libraries/queryBuilder/",
            Array(
                'Expressionable',
                'ResultSet',
                'Connection',
                'Expression',
                'Query',
                'Connection_Proxy',
                'Cnnection_Counter',
                'Connection_Dumper',
                'Query_MySQL',
                'Expression_MySQL'
            )
        );
    }

    /**
     * http index 진입점
     *
     * @throws RouteException
     */
    public function wakeUp()
    {
        session_start();
        $this->autoLoad();

        if (!Router::match()) {
            throw new RouteException('페이지를 찾을수 없습니다.', 405);
        }
    }

    /**
     * CLI 환경에서 진입점
     *
     * @throws CommandException
     * @throws RouteException
     */
    public function commandWakeUp()
    {
        session_start();
        $this->autoLoad();

        Config::set('isCommand', true);

        if ($_SERVER['argc'] < 2) {
            throw new CommandException("Invalid Arguments \nphp command [command_router] [parameter]", 405);
        }

        $requestUrl     = $_SERVER['argv'][1];
        $requestMethod  = METHOD_COMMAND;

        //Debug::display(Router::getRoutes());

        if (!Router::match($requestUrl, $requestMethod)) {
            throw new CommandException("Not found router \nphp command [command_router] [parameter]", 405);
        }
    }
}