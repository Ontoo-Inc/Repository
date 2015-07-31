<?php

namespace Ontoo\Repositories\Contracts;

/**
 * Interface RepositoryPresenterInterface
 *
 * @package Ontoo\Repositories\Contracts
 */
interface RepositoryPresenterInterface
{
    /**
     * @param PresenterInterface $presenter
     *
     * @return mixed
     */
    public function setPresenter(PresenterInterface $presenter);

    /**
     * @param bool|true $status
     *
     * @return mixed
     */
    public function skipPresenter($status = true);
}
