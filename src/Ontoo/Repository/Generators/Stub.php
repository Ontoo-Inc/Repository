<?php

namespace Ontoo\Repositories\Generators;

class Stub
{
    protected $filepath;
    /**
     * @var null
     */
    protected $replacements;

    /**
     * Stub constructor.
     *
     * @param string $filepath
     * @param array $replacements
     */
    public function __construct($filepath, $replacements = [])
    {
        $this->filepath = $filepath;
        $this->replacements = $replacements;

        return $this->getContent();
    }

    public function getContent()
    {
        $content = file_get_contents($this->getPath());
        foreach ($this->replacements as $search => $replace) {
            $content = str_replace('$' . strtoupper($search) . '$', $replace, $content);
        }

        return $content;
    }

    private function getPath()
    {
        return $this->filepath;
    }

    public function __toString()
    {
        return $this->getContent();
    }
}
