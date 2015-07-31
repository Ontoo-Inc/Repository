<?php

namespace Ontoo\Repository\Criteria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Ontoo\Repository\Contracts\CriteriaInterface;
use Ontoo\Repository\Contracts\RepositoryInterface;

/**
 * Class OrderByCriteria
 *
 * @package Ontoo\Repository\Criteria
 */
class OrderByCriteria implements CriteriaInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * orderByCriteria constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply(Model $model, RepositoryInterface $repository)
    {
        $orderBy = $this->request->get(
            config('repository.criteria.params.orderBy'),
            ($repository->orderBy) ?: 'id'
        );
        $sortBy = $this->request->get(
            config('repository.criteria.params.sortBy'),
            ($repository->sortBy) ?: 'asc'
        ) ?: 'asc';
        if (isset($orderBy)) {
            $model = $model->orderBy($orderBy, $sortBy);
        }

        return $model;
    }
}
