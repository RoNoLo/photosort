<?php

namespace RoNoLo\PhotoSort\Iterator;

class ExtensionRecursiveFilterIterator extends \RecursiveFilterIterator
{
    private $extensions = [];

    public function __construct(\RecursiveIterator $iterator, $extensions = [])
    {
        $this->extensions = $extensions;

        parent::__construct($iterator);
    }

    public function accept() {
        if ($this->current()->isDir()) {
            return false;
        }

        return in_array($this->current()->getExtension(), $this->extensions);
    }
}