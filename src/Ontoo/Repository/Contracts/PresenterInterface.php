<?php

namespace Ontoo\Repository\Contracts;

/**
 * Interface PresenterInterface
 *
 * @package Ontoo\Repository\Contracts
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
