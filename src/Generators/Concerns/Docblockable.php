<?php

namespace LaraSpells\Generators\Concerns;

use Closure;
use LaraSpells\Generators\DocblockGenerator;

trait Docblockable
{

    protected $docblock;

    /**
     * Set class docblock.
     *
     * @param  Closure $callback
     * @return void
     */
    public function setDocblock(Closure $callback)
    {
        $this->docblock = new DocblockGenerator;
        $callback($this->docblock);
    }

    /**
     * Get class docblock.
     *
     * @return null|LaraSpells\Generators\DocblockGenerator
     */
    public function getDocblock()
    {
        return $this->docblock;
    }

    /**
     * Set or modify docblock.
     *
     * @param  Closure $callback
     * @return void
     */
    public function docblock(Closure $callback)
    {
        $docblock = $this->getDocblock();
        if ($docblock) {
            $callback($docblock);
        } else {
            $this->setDocblock($callback);
        }
    }
}
