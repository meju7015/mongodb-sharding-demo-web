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

        $model = new HomeModel();

        print_r($model->find());


        $this->view
            ->load('home', 'index', $viewData)
            ->display();
    }
}