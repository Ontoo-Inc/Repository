<?php

namespace Ontoo\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface CriteriaInterface
 *
 * @package Ontoo\Repositories\Contracts
 */
interface CriteriaInterface
{
    /**
     * @param $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply(Model $model, RepositoryInterface $repository);
}
