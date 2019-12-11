<?php
/**
 * Config 클래스
 *
 * @const DEBUG               : true 설정하면 error_report 가 E_ALL 로 설정됩니다.
 * @const IS_SUBDIRECTORY     : true 설정하면 절대경로에서 상대경로로 변경됩니다.
 * @const APPLICATION_NAME    : HTML title
 * @const DEFAULT_SITE        : 상위 디렉토리
 * @const DEFAULT_PUBLIC      : public 디렉토리의 이름
 * @const DEFAULT_DESCRIPTION : HTML Meta Info (description)
 * @const DEFAULT_THEME       : resource/views 디렉토리의 하위 디렉토리 선택자입니다.
 * @const DEFAULT_TEMPLATE    : HTML 템플릿을 선택할 수 있습니다. 다른 선택을 위해서는 npm 필요.
 *
 * @var $isCommand            : CLI 환경에서 실행되었는지 여부가 저장됩니다.
 * @var $logPath              : Log 저장 경로입니다.
 * @var $exceptionLogPath     : 예외 로그 저장 경로입니다.
 *
 * User: mason
 * Date: 2019-09-20
 * Time: 오후 1:18
 */
class Config
{
    const DEBUG                     = false;
    const IS_SUBDIRECTORY           = false;
    const APPLICATION_NAME          = 'Hackers | Developer';
    const DEFAULT_SITE              = '/';

    /*
     * Not default virtual host framework/public
     * set DEFAULT_PUBLIC = public
     */
    const DEFAULT_PUBLIC            = '';
    const DEFAULT_DESCRIPTION       = 'uFramework v1.0';
    const DEFAULT_THEME             = 'basic';
    const DEFAULT_TEMPLATE          = 'html';

    public static $isCommand        = false;

    public static $logPath          = '/copy/storage/logs';
    public static $exceptionLogPath = '/copy/storage/logs/exception';

    /**
     * @return string 절대경로를 반환합니다.
     */
    public static function getRootDir()
    {
        $thisFile = __FILE__;
        $explode = explode('app', $thisFile);

        return $explode[0];
    }

    /**
     * setter
     *
     * @param $var
     * @param $value
     */
    public static function set($var, $value)
    {
        self::${$var} = $value;
    }
}