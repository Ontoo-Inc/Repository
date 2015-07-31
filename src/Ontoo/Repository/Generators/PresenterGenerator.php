<?php

namespace Ontoo\Repositories\Generators;

class PresenterGenerator extends Generator
{
    protected $stub = 'presenter';

    public function getReplacements()
    {
        return array_merge(parent::getReplacements(), [
            'transformer' => $this->getOption('transformer') ?: parent::getRootNamespace() . 'Repositories\\Transformers\\' . $this->getName()
        ]);
    }

    public function getRootNamespace()
    {
        return parent::getRootNamespace() . 'Repositories\\Presenters\\';
    }

    public function getPath()
    {
        return $this->getBasePath() . '/Repositories/Presenters/' . $this->getName() . 'Presenter.php';
    }
}
