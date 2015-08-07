<?php

namespace Ontoo\Repository\Contracts;

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
    public function apply($model, RepositoryInterface $repository);
}
