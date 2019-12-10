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

    public function __construct()
    {
        $this->rootDir = Config::getRootDir();

        $dbInfo = Env::getDBConnectInfo();

        try {
            if (is_array($dbInfo)) {
                foreach ($dbInfo as $key => $item) {
                    $this->$key = Connection::connect(
                        $item['dsn'],
                        $item['username'],
                        $item['password']
                    );
                }
            } else {
                throw new PDOException('PDO Connection error', 500);
            }

            $this->builder = new Query(Array(
                'connection'        => $this->master,
                'slaveConnection'   => $this->slave
            ));

        } catch (PDOException $e) {
            // TODO :: slave key 가 connection 이 안되어있으면 select query 를 master로 보내고 로그를 쌓게한다..

            $viewException = new ModelException('PDO connect error : '.$e->getMessage(), 500);
            $viewException->display();
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
            UDebug::store(Array(
                'model' => $model,
                'path'  => $modelFile
            ), 'model');

            return new $model();
        } else {
            throw new ModelException('모델 파일을 찾을 수 없습니다.', 405);
        }
    }
}