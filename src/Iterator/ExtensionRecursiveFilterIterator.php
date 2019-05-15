<?php

class ExtensionRecursiveFilterIterator extends RecursiveFilterIterator {
    private $extensions = [];

    public function __construct(RecursiveIterator $iterator, $extensions = [])
    {
        $this->extensions = $extensions;

        parent::__construct($iterator);
    }

    public function accept() {
        return in_array($this->current()->getExtension(), $this->extensions, true);
    }

}