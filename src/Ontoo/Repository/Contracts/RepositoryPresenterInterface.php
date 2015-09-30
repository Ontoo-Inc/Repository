<?php

namespace Ontoo\Repository\Contracts;

/**
 * Interface RepositoryPresenterInterface
 *
 * @package Ontoo\Repository\Contracts
 */
interface RepositoryPresenterInterface
{
    /**
     * @param $presenter
     *
     * @return mixed
     */
    public function setPresenter($presenter);

    /**
     * @param bool|true $status
     *
     * @return mixed
     */
    public function skipPresenter($status = true);
}
