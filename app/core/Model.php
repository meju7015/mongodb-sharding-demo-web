<?php
/**
 * 모델 클래스
 */
class Model
{
    /**
     * PDO 객체
     *
     * @var PDO
     */
    protected $connect;

    /**
     * 최상위 루트
     *
     * @var
     */
    protected $rootDir;

    /**
     * 쿼리빌더 Object
     *
     * @var QueryBuilder
     */
    public $builder;

    public static $connectFactory;

    public function __construct()
    {
        if (!Model::$connectFactory) {
            $this->rootDir = Config::getRootDir();

            $dbInfo = Env::getDBConnectInfo();

            Debug::display($dbInfo);

            try {
                Model::$connectFactory = new MongoDB\Client($dbInfo['master']);
                $this->connect = Model::$connectFactory;
                /*if (is_array($dbInfo)) {
                    foreach ($dbInfo as $key => $item) {
                        $this->$key = new MongoDB\Client($item);
                    }
                } else {
                    throw new PDOException('PDO Connection error', 500);
                }*/
            } catch (PDOException $e) {
                $viewException = new ModelException('PDO connect error : ' . $e->getMessage(), 500);
                $viewException->display();
            }
        } else {
            $this->connect = Model::$connectFactory;
        }
    }

    /**
     * 모델 로드
     *
     * @param string    $model
     * @return Object|false
     * @throws ModelException
     */
    public function loadModel($model)
    {
        $modelFile = "{$this->rootDir}app/models/{$model}.php";

        if (file_exists($modelFile)) {
            Debug::store(Array(
                'model' => $model,
                'path'  => $modelFile
            ), 'model');

            return new $model();
        } else {
            throw new ModelException('모델 파일을 찾을 수 없습니다.', 405);
        }
    }
}