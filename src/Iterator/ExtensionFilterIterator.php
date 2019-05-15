<?php

namespace RoNoLo\PhotoSort\Iterator;

class ExtensionFilterIterator extends \FilterIterator {
    private $extensions = [];

    public function __construct(\Iterator $iterator, $extensions = [])
    {
        $this->extensions = $extensions;

        parent::__construct($iterator);
    }

    public function accept() {
        return in_array($this->current()->getExtension(), $this->extensions, true);
    }

}