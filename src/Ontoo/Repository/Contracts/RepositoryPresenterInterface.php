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
