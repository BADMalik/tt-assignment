<?php


namespace App\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;

abstract class BaseRepository
{
    use ForwardsCalls;
    private $model;

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * @param Application $app
     */
    public function __construct()
    {
        $this->app = app();
        $this->makeModel();
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if(strpos($method,'fetch') !== false)
        {
            $method = substr($method, strlen('fetch'));
            array_shift($parameters);
            $method = lcfirst($method);
        }

        return $this->forwardCallTo($this->model, $method, $parameters);
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $method = 'fetch'. ucfirst($method);
        return (new static)->forwardScopeCall($method, $parameters);
    }

    /**
     * Forward call to fetch scope method of the repository
     * Adding query object to objects
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function forwardScopeCall($method, $parameters)
    {
        return $this->$method($this->model,...$parameters);
    }

    /**
     * @return Model
     * @throws RepositoryException
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model)
        {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }
        $this->model = $model;
        return $this->model;
    }


    /**
     * Return if record exists or not
     * @param $id
     * @param array $with
     * @return mixed
     */
    public function fetchAssertRecord($query,$id, $with = [])
    {
        $model = $this->model;
        if(!empty($with))
        {
            $model = $model->with($with);
        }
        return $model->findOrFail($id);
    }

    
    /**
     * fetchIndex
     *
     * @param  mixed $query
     * @return collection
     */
    public function fetchIndex($query) 
    {
        return $this->all();
    }

    
    /**
     * fetchShow
     *
     * @param  mixed $query
     * @param  int $id
     * @return model
     */
    public function fetchShow($query, $id) 
    {
        return $this->entity($id);
    }

    
    /**
     * fetchStore
     *
     * @param  mixed $query
     * @param  array $requestData
     * @return model
     */
    public function fetchStore($query, $requestData) 
    {
        return $this->create($requestData);
    }

    
    /**
     * fetchUpdate
     *
     * @param  mixed $query
     * @param  array $requestData
     * @param  int $id
     * @return bool
     */
    public function fetchUpdate($query, $requestData, $id) 
    {
        return $this->entity($id)->update($requestData);
    }
 
     
    /**
     * fetchDestroy
     *
     * @param  mixed $query
     * @param  int $id
     * @return bool
     */
    public function fetchDestroy($query, $id) 
    {
        return $this->entity($id)->delete();
    }

    private function entity($idOrModel) {
        return $idOrModel instanceof ($this->model()) ? $idOrModel : $this->findOrFail($idOrModel);
    }


    /**
     * Success result response
     *
     * @param string $msg
     * @param array $data
     * @param int $code
     * @return array
     */
    protected function success($msg = 'Process implemented successfully', $data = [], $code = 200)
    {
        return [
            'success' => true,
            'message' => $msg,
            'code' => $code,
            'data' => $data,
        ];
    }

    /**
     * Error result response
     *
     * @param string $msg
     * @param int $code
     * @return array
     */
    protected function error($msg = 'Error while processing', $code = 400)
    {
        return [
            'success' => false,
            'message' => $msg,
            'code' => $code,
        ];
    }
}
