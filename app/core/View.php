<?php
/**
 * View 클래스
 * User: mason
 * Date: 2019-09-19
 * Time: 오후 5:49
 */
class View
{
    /**
     * View Codes
     *
     * @var
     */
    protected $view;

    /**
     * Html header
     *
     * @var array
     */
    public $head;

    /**
     * Extract data
     *
     * @var
     */
    public $data;

    /**
     * Theme directory
     *
     * @var string
     */
    protected $theme;

    /**
     * Template extension (default html)
     *
     * @var string
     */
    protected $template;

    /**
     * 절대경로
     *
     * @var
     */
    protected $rootDir;

    public function __construct()
    {
        $this->theme = Config::DEFAULT_THEME;
        $this->template = Config::DEFAULT_TEMPLATE;
        $this->rootDir = Config::getRootDir();

        $topGNB = [
            'Extends' => [
                'sub' => [
                    '클립보더' => [
                        'router' => Config::DEFAULT_SITE.'/clip',
                    ],
                    '해시/암호화' => [
                        'router' => Config::DEFAULT_SITE.'/crypt',
                    ],
                    'Converter' => [
                        'router' => Config::DEFAULT_SITE.'/convert',
                    ],
                ]
            ],
            'Uploader' => [
                'router' => Config::DEFAULT_SITE.'/upload',
            ],
        ];

        $this->head = Array(
            'title' => Config::APPLICATION_NAME,
            'meta'  => Array(
                'description' => Config::DEFAULT_DESCRIPTION
            ),
            'js' => Array(
                'common' => Config::DEFAULT_PUBLIC.'/js/copy/config/common.js',
                'jquery' => Config::DEFAULT_PUBLIC.'/js/jquery/jquery-3.4.1.min.js',
                'bootstrap' => Config::DEFAULT_PUBLIC.'/js/bootstrap/bootstrap.bundle.js'
            ),
            'css' => Array(
                'bootstrap' => Config::DEFAULT_PUBLIC.'/css/bootstrap/bootstrap.min.css'
            )
        );

        $this->data = Array(
            'session' => '',
            'debug' => Debug::pop(),
            'CSRF_TOKEN' => Security::getCSRFDetect(),
            'topGNB' => $topGNB
        );
    }

    /**
     * 뷰 로드
     *
     * @param string    $view
     * @param string    $endPoint
     * @param array     $data
     * @return false|string
     * @throws ViewException
     */
    public function load($view, $endPoint, $data = null)
    {
        $view = strtolower($view);

        $this->data['head'] = $this->head;
        $viewFile = "{$this->rootDir}/resources/views/{$this->theme}/{$view}/{$endPoint}.{$this->template}";

        if (file_exists($viewFile)) {
            ob_start();

            extract($this->data);

            if (is_array($data)) {
                extract($data);
            }

            include_once $viewFile;
            $this->view[] =  ob_get_clean();

            return $this;
        } else {
            throw new ViewException('뷰 파일을 찾을 수 없습니다. EndPoint 를 확인하세요.', 405, $viewFile);
        }
    }

    public function display()
    {
        print(join($this->view));
    }
}