<?php
/**
 * 컨트롤러 클래스
 * 5.4 이하 버전용
 */
class Controller
{
    protected $view;
    protected $user;

    protected $topGNB;

    public function __construct()
    {
        $_SESSION['CSRF_TOKEN'] = Security::getCSRFDetect();

        $this->user = $_SESSION;


        Debug::store($this->user, 'session');

        $this->view = new View();

        $this->view->data['user'] = $this->user;
    }
}