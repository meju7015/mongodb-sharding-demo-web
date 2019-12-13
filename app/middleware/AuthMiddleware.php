<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-11-28
 * Time: 오전 10:35
 */
class AuthMiddleware
{
    public function apiAuth($router, $user)
    {
        list($_method, $_route, $_target, $_name, $_middleware) = $router;

        if (strtoupper($_method) != $_SERVER['REQUEST_METHOD']) {
            header('method invalid', '', 404);
            exit;
        }

        if (strcasecmp(Env::get('app_key'), $this->token($_POST['token'])) != 0 || !$_POST['userID']) {
            header('invalid user token', '', 405);
            exit;
        }

        unset($_POST['token']);

        return true;
    }

    private function token($data)
    {
        return md5($data);
    }

    public function logged($router, $user)
    {
        if (!$user['logged']) {
            Router::follow(Config::DEFAULT_SITE."/login");
        }

        return true;
    }
}