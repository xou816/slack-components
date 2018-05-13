<?php

namespace SlackComponents\Components;

class ArrayComponent extends AbstractComponent {

    private $arr;

    public function __construct(array $arr) {
        $this->arr = $arr;
    }

    protected function buildTree($state) {
        return $this->arr;
    }

    protected function isInterestedIn($patch) {
        return true;
    }

    protected function defaultState() {
        return [];
    }
}