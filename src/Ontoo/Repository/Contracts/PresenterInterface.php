<?php

namespace Ontoo\Repositories\Contracts;

/**
 * Interface PresenterInterface
 *
 * @package Ontoo\Repositories\Contracts
 */
interface PresenterInterface
{
    /**
     * @param $data
     *
     * @return mixed
     */
    public function present($data);
}
