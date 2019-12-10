<?php

/**
 * �� Ŭ����
 */
class Model
{
    /**
     * PDO ��ü
     * 
     * @var PDO 
     */
    protected $connect;

    /**
     * �ֻ��� ��Ʈ
     *
     * @var
     */
    protected $rootDir;

    /**
     * �������� Object
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
            // TODO :: slave key �� connection �� �ȵǾ������� select query �� master�� ������ �α׸� �װ��Ѵ�..

            $viewException = new ModelException('PDO connect error : '.$e->getMessage(), 500);
            $viewException->display();
        }
    }

    /**
     * �� �ε�
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
            throw new ModelException('�� ������ ã�� �� �����ϴ�.', 405);
        }
    }
}