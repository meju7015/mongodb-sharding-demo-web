<?php

/**
 * ��Ʈ�ѷ� Ŭ����
 * 5.4 ���� ������
 */
class Controller
{
    protected $model;
    protected $view;
    protected $user;

    public function __construct()
    {
        session_start();

        $_SESSION['CSRF_TOKEN'] = Security::getCSRFDetect();

        $this->user = $_SESSION;

        UDebug::store($this->user, 'session');

        $this->model = new Model();
        $this->view = new View();
    }
}