<?php
class ListController extends Controller implements Controllable
{
    public function index($request, $params)
    {
        return true;
    }

    public function controller($params)
    {
        $controllerPath = Config::getRootDir()."app/controllers";

        $colors = new Colors();
        $list = array_diff(scandir($controllerPath), Array('.', '..'));

        echo "============================================================================================================================================================\n";
        echo $colors->getColoredString("Name\t\t", "yellow");
        echo $colors->getColoredString("Path\n", "yellow");
        echo "============================================================================================================================================================\n";
        foreach ($list as $controller) {
            $path = $controllerPath.'/'.$controller;
            $name = str_replace('.php', '', str_replace('Controller', '', $controller));
            echo $colors->getColoredString($name, "light_green")."\t\t";
            echo $colors->getColoredString($path, "light_green")."\n";
        }
        echo "============================================================================================================================================================\n";
    }

    public function model($params)
    {
        $modelPath = Config::getRootDir()."app/models";

        $colors = new Colors();
        $list = array_diff(scandir($modelPath), Array('.', '..'));

        echo "============================================================================================================================================================\n";
        echo $colors->getColoredString("Name\t\t", "yellow");
        echo $colors->getColoredString("Path\n", "yellow");
        echo "============================================================================================================================================================\n";
        foreach ($list as $model) {
            $path = $modelPath.'/'.$model;
            $name = str_replace('.php', '', str_replace('Model', '', $model));
            echo $colors->getColoredString($name,"light_green")."\t\t";
            echo $colors->getColoredString($path,"light_green")."\n";
        }
        echo "============================================================================================================================================================\n";
    }

    public function router($params)
    {
        $routes = Router::getRoutes();

        $colors = new Colors();

        echo "============================================================================================================================================================\n";
        echo $colors->getColoredString("Router\t\t\t\t", "yellow");
        echo $colors->getColoredString("Method\t\t", "yellow");
        echo $colors->getColoredString("Target\t\t\t\t\t", "yellow");
        echo $colors->getColoredString("Name\t\t\t\t", "yellow");
        echo $colors->getColoredString("Middleware\n", "yellow");
        echo "============================================================================================================================================================\n";

        sort($routes, 3);

        foreach ($routes as $key => $item) {
            list($method, $router, $target, $name, $middleware) = $item;
            echo $colors->getColoredString(str_pad($router, 20, ' ')."\t\t", "light_green");
            echo $colors->getColoredString(str_pad($method, 5, ' ')."\t\t", "light_green");
            echo $colors->getColoredString(str_pad($target, 25, ' ')."\t\t", "light_green");
            echo $colors->getColoredString(str_pad($name, 25, ' ')."\t", "light_green");
            echo $colors->getColoredString(str_pad($middleware, 35, ' ')."\n", "light_green");
        }
        printf("============================================================================================================================================================\n");
    }

    public function all($params)
    {
        $colors = new Colors();

        $routes = Router::getRoutes();
        $models = array_diff(scandir(Config::getRootDir()."app/models"), ['.', '..']);

        // models
        foreach ($models as $key => $item) {
            $name = str_replace('.php', '', str_replace('Model', '', $item));
            $_models[$name] = $item;
        }

        // parse
        foreach ($routes as $key => $item) {
            list($method, $router, $target, $name, $mmiddleware) = $item;
            list($controller, $function) = explode('.', $target);

            if ($mmiddleware) {
                // TODO::묘델이랑 컨트롤러 연결되는 부분 찾아서 배열로 합쳐
            }
        }
    }

    /*protected function display($columns, $values)
    {
        if (!is_array($columns) && !is_array($values)) {
            throw new CommandException('명령을 실행했지만 Display 되지 않았습니다', 405);
        }

        $defaultLineCount   = 25;
        $defaultLineChar    = '=';
        $defaultPadChar     = ' ';
        $columnLenth        = sizeof($columns);
        $valuesPad          = 30;
        $lineCount          = $defaultLineCount * $columnLenth;

        for ($i = 0; $i < $lineCount; $i++) {
            echo $defaultLineChar;
        }

        $columns = implode("\t\t\t", $columns);

        echo PHP_EOL;
        echo $columns . PHP_EOL;

        for ($i = 0; $i < $lineCount; $i++) {
            echo $defaultLineChar;
        }

        echo PHP_EOL;

        print_r($values);

        foreach ($values as $key => $value) {

        }
    }*/
}