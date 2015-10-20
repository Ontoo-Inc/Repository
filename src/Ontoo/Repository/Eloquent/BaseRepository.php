<?php

namespace Ontoo\Repository\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use Ontoo\Repository\Contracts\CriteriaInterface;
use Ontoo\Repository\Contracts\PresenterInterface;
use Ontoo\Repository\Contracts\RepositoryCriteriaInterface;
use Ontoo\Repository\Contracts\RepositoryInterface;
use Ontoo\Repository\Contracts\RepositoryPresenterInterface;
use Ontoo\Repository\Exceptions\RepositoryException;

/**
 * Class BaseRepository
 *
 * @package Ontoo\Repository\Eloquent
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
     * @var array
     */
    protected $relations = [];

    /**
     * @var
     */
    protected $presenter;

    /**
     * @var bool
     */
    protected $skipPresenter = false;

    /**
     * @var string
     */
    protected $orderField = null;

    /**
     * @var string
     */
    protected $orderDirection = 'asc';


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
    protected function resetModel()
    {
        $this->makeModel();
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

        if (! is_null($presenter)) {
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
     * @param $presenter
     *
     * @return $this
     * @throws RepositoryException
     */
    public function setPresenter($presenter)
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
     * take limit data of repository
     *
     * @param int $limit
     *
     * @return $this
     */
    public function take($limit)
    {
        $this->model = $this->model->take($limit);

        return $this;
    }


    /**
     * Add 'order by' clause to the query.
     *
     * @param $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

    /**
     * Get Model's PrimaryKey
     *
     * @return string
     */
    public function getKeyName()
    {
        if ($this->model instanceof Model) {
            return $this->model->getKeyName();
        }

        $model = $this->app->make($this->model());

        return $model->getKeyName();
    }

    /**
     * @return $this
     */
    public function applyOrder()
    {
        if (count($this->model->getQuery()->orders) === 0) {
            $field = ($this->orderField !== null) ? $this->orderField : $this->getKeyName();
            $direction = strtolower($this->orderDirection) === 'asc' ? 'asc' : 'desc';

            $this->model = $this->model->orderBy($field, $direction);
        }

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*'], $isCount = false)
    {
        $this->eagerLoading();
        $this->applyCriteria();

        if ($isCount) {
            $results = $this->model->count();
        } else {
            $this->applyOrder();

            if ($this->model instanceof Builder) {
                $results = $this->model->get($columns);
            } else {
                $results = $this->model->all($columns);
            }
        }

        $this->resetModel();

        return $isCount ? $results : $this->parseResult($results);
    }

    /**
     * @param null $perPage
     * @param array $columns
     *
     * @return mixed
     */
    public function paginate($perPage = null, $columns = ['*'])
    {
        $this->eagerLoading();
        $this->applyCriteria();
        $this->applyOrder();
        $perPage = $perPage ?: config('repository.pagination.perPage', 25);
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
        $results = $this->model->create($data);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * Retrieve matched entity or create new one.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function firstOrCreate(array $data)
    {
        if ($entity = $this->take(1)->findWhere($data)->first()) {
            return $entity;
        }

        $this->resetModel();

        return $this->create($data);
    }

    /**
     * @param array $data
     * @param $id
     * @return mixed
     * @throws RepositoryException
     */
    public function update(array $data, $id)
    {
        $_skipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model = $this->find($id);
        $model->fill($data);
        $model->save();

        $this->skipPresenter($_skipPresenter);

        $this->makeModel();

        return $this->parseResult($model);
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
        $this->eagerLoading();
        $this->applyCriteria();
        $results = $this->model->findOrFail($id, $columns);
        $this->resetModel();

        return $this->parseResult($results);
    }

    /**
     * @param $field
     * @param $value
     * @param array $columns
     * @param bool $isCount
     *
     * @return mixed
     */
    public function findBy($field, $value, $columns = ['*'], $isCount = false)
    {
        $this->eagerLoading();
        $this->applyCriteria();
        if ($isCount) {
            $counts = $this->model->where($field, $value)->count();
        } else {
            $this->applyOrder();
            $results = $this->model->where($field, $value)->get($columns);
        }
        $this->resetModel();

        return $isCount ? $counts : $this->parseResult($results);
    }

    /**
     * @param array $where
     * @param array $columns
     * @param bool $isCount
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'], $isCount = false)
    {
        $this->eagerLoading();
        $this->applyCriteria();

        if (!$isCount) {
            $this->applyOrder();
        }

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, $value);
            }
        }

        if ($isCount) {
            $counts = $this->model->count();
        } else {
            $results = $this->model->get($columns);
        }
        $this->resetModel();

        return $isCount ? $counts : $this->parseResult($results);
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

        $this->relations = array_merge($this->relations, $relations);

        return $this;
    }

    /**
     * @return $this
     */
    protected function eagerLoading()
    {
        if (! is_null($this->relations)) {
            $this->model = $this->model->with($this->relations);
        }

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
        $this->applyOrder();
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
    protected function parseResult($result)
    {
        if ($this->presenter instanceof PresenterInterface) {
            if (! $this->skipPresenter) {
                return $this->presenter->present($result);
            }
        }

        return $result;
    }
}
