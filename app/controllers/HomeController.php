<?php

/**
 * 사용자 정의 컨트롤러
 */
class HomeController extends Controller implements Controllable
{
    public function index($request, $params)
    {
        $viewData = Array(
            'allow' => true
        );

        $this->view
            ->load('home', 'index', $viewData)
            ->display();
    }

    public function command($request, $params)
    {
        $model = new HomeModel();

        $result = $model->query($request['command']);

        print Json::out([
            'json' => $result
        ]);

        exit;
    }

    /**
     *
     *
     * @param $request
     */
    public function find($request)
    {
        $model = new HomeModel();

        $json = $model->find($request);

        print json::out([
            'success' => true,
            'json' => $json
        ]);
        exit;
    }

    /**
     *
     *
     * @param $request
     */
    public function insert($request)
    {

    }

    /**
     *
     *
     * @param $request
     */
    public function update($request)
    {

    }

    /**
     *
     *
     * @param $request
     */
    public function replace($request)
    {

    }

    /**
     *
     *
     * @param $request
     */
    public function delete($request)
    {

    }
}