<?php

namespace Ontoo\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use Ontoo\Repositories\Contracts\CriteriaInterface;
use Ontoo\Repositories\Contracts\PresenterInterface;
use Ontoo\Repositories\Contracts\RepositoryCriteriaInterface;
use Ontoo\Repositories\Contracts\RepositoryInterface;
use Ontoo\Repositories\Contracts\RepositoryPresenterInterface;
use Ontoo\Repositories\Exceptions\RepositoryException;
use Prettus\Validator\Contracts\ValidatorInterface;

/**
 * Class BaseRepository
 *
 * @package Ontoo\Repositories\Eloquent
 */
abstract class BaseRepository implements RepositoryInterface, RepositoryCriteriaInterface, RepositoryPresenterInterface
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Collection
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * Fields for RequestCriteria.
     *
     * @var array
     */
    protected $searchableFields = [];

    /**
     * @var
     */
    protected $validator;

    /**
     * @var null
     */
    protected $rules = null;
    /**
     * @var
     */
    protected $presenter;
    /**
     * @var bool
     */
    protected $skipPresenter = false;

    public $orderBy = null;

    public $sortBy = null;

    /**
     * BaseRepository constructor.
     *
     * @param $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->criteria = new Collection();
        $this->makeModel();
        $this->makePresenter();
        $this->makeValidator();
        $this->boot();
    }

    /**
     * Set model for repository.
     *
     * @return string
     */
    abstract public function model();

    /**
     * @throws RepositoryException
     */
    private function makeModel()
    {
        $model = $this->app->make($this->model());

        if (! $model instanceof Model) {
            throw new RepositoryException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $this->model = $model;
    }

    /**
     * @throws RepositoryException
     */
    private function resetModel()
    {
        $this->makeModel();
    }

    /**
     * @return null|\Prettus\Validator\LaravelValidator
     */
    public function validator()
    {
        if (isset($this->rules) && ! is_null($this->rules) && is_array($this->rules) && ! empty($this->rules)) {
            $validator = $this->app->make(\Prettus\Validator\LaravelValidator::class);

            if ($validator instanceof ValidatorInterface) {
                $validator->setRules($this->rules);

                return $validator;
            }
        }

        return null;
    }

    /**
     * @return null|ValidatorInterface
     * @throws RepositoryException
     */
    private function makeValidator()
    {
        $validator = $this->validator();

        if (! is_null($validator)) {
            $this->validator = $validator;

            if (! $this->validator instanceof ValidatorInterface) {
                throw new RepositoryException(
                    "Class {$validator} must be an instance of Prettus\\Validator\\Contracts\\ValidatorInterface"
                );
            }

            return $this->validator;
        }

        return null;
    }

    /**
     * @param null $presenter
     *
     * @return null|PresenterInterface
     * @throws RepositoryException
     */
    private function makePresenter($presenter = null)
    {
        $presenter = (! is_null($presenter)) ? $presenter : $this->presenter();

        if ( ! is_null($presenter)) {
            $this->presenter = $this->app->make($presenter);

            if (! $this->presenter instanceof PresenterInterface) {
                throw new RepositoryException(
                    "Class {$presenter} must be an instance of Ontoo\\Repositories\\Contracts\\PresenterInterface"
                );
            }

            return $this->presenter;
        }

        return null;
    }

    /**
     * @param PresenterInterface $presenter
     *
     * @return $this
     * @throws RepositoryException
     */
    public function setPresenter(PresenterInterface $presenter)
    {
        $this->makePresenter($presenter);

        return $this;
    }

    /**
     * Boot the repository.
     */
    public function boot()
    {
    }

    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        $this->applyCriteria();
        $results = $this->model->all($columns);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param null $perPage
     * @param array $columns
     *
     * @return mixed
     */
    public function paginate($perPage = null, $columns = ['*'])
    {
        $this->applyCriteria();
        $perPage = is_null($perPage) ? config('repository.pagination.perPage', 25) : $perPage;
        $results = $this->model->paginate($perPage, $columns);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data)
    {
        if (! is_null($this->validator)) {
            $this->validator
                ->with($data)
                ->passOrFail(ValidatorInterface::RULE_CREATE);
        }

        $results = $this->model->create($data);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param array $data
     * @param $id
     * @param string $field
     *
     * @return mixed
     */
    public function update(array $data, $id, $field = 'id')
    {
        if (array_key_exists('_token', $data)) {
            unset($data['_token']);
        }
        if (array_key_exists('_method', $data)) {
            unset($data['_method']);
        }
        if (! is_null($this->validator)) {
            $this->validator
                ->with($data)
                ->passOrFail(ValidatorInterface::RULE_UPDATE);
        }

        $results = $this->model->where($field, $id)->update($data);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    /**
     * @param $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $results = $this->model->findOrFail($id, $columns);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param $field
     * @param $value
     * @param array $columns
     *
     * @return mixed
     */
    public function findBy($field, $value, $columns = ['*'])
    {
        $this->applyCriteria();
        $results = $this->model->where($field, $value)->get($columns);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        $this->applyCriteria();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, $value);
            }
        }

        $results = $this->model->get($columns);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param array|string $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSearchableFields()
    {
        return $this->searchableFields;
    }

    /**
     * @param bool|false $status
     *
     * @return $this
     */
    public function skipCriteria($status = false)
    {
        $this->skipCriteria = $status;

        return $this;
    }


    /**
     * @return Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param CriteriaInterface|string $criteria
     *
     * @return Collection
     */
    public function getByCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = $this->app->make($criteria);
        }

        $this->model = $criteria->apply($this->model, $this);
        $results = $this->model->get();
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param CriteriaInterface|string $criteria
     *
     * @return $this
     */
    public function pushCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = $this->app->make($criteria);
        }

        $this->criteria->push($criteria);

        return $this;
    }

    /**
     * @return $this
     */
    public function applyCriteria()
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        $criterias = $this->getCriteria();

        if ($criterias) {
            foreach ($criterias as $criteria) {
                if ($criteria instanceof CriteriaInterface) {
                    $this->model = $criteria->apply($this->model, $this);
                }
            }
        }

        return $this;
    }

    /**
     * Set presenter for reposiroty.
     *
     * @return mixed
     */
    public function presenter()
    {
        return null;
    }

    /**
     * @param bool|true $status
     *
     * @return $this
     */
    public function skipPresenter($status = true)
    {
        $this->skipPresenter = $status;

        return $this;
    }

    /**
     * @param $result
     *
     * @return mixed
     */
    private function parseResult($result)
    {
        if ($this->presenter instanceof PresenterInterface) {
            if (! $this->skipPresenter) {
                return $this->presenter->present($result);
            }
        }

        return $result;
    }
}
