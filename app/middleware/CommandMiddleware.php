<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-12-02
 * Time: 오전 11:07
 */
class CommandMiddleware
{
    public function ipFilter()
    {
        $ipTable = [
            '10\.0\.2\.',
            '10\.10\.',
            '10\.11\.',
            '10\.12\.',
            '192\.16\.',
            '192\.168\\.',
            '172\.20\.',
            '172\.16\.1\.'
        ];

        /**
         * HTTP로 접근하거나 SSH로 접근할 경우를 대비
         */
        if (!empty($_SERVER['XFFCLIENTIP'])) {
            $realIP = $_SERVER['XFFCLIENTIP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['SSH_CLIENT'])) {
            $realIP = explode(' ', $_SERVER['SSH_CLIENT'])[0];
        } else {
            $realIP = $_SERVER['REMOTE_ADDR'];
        }

        if ($realIP && !preg_match('/'.implode('|', $ipTable).'/', $realIP)){
            header('permission denied', 404);
            exit;
        }

        return true;
    }
}