<?php

namespace Ontoo\Repositories\Exceptions;

use Exception;

/**
 * Class FileExistsException
 *
 * @package Ontoo\Repositories\Exceptions
 */
class FileExistsException extends Exception
{
    /**
     * @var string
     */
    protected $path;

    /**
     * FileExistsException constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;

        parent::__construct('File already exists at path: '.$this->getPath());
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
