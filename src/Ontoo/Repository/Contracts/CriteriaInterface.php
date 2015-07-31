<?php

namespace Ontoo\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface CriteriaInterface
 *
 * @package Ontoo\Repository\Contracts
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
