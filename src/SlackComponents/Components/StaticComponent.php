<?php

namespace SlackComponents\Components;

abstract class StaticComponent extends AbstractComponent {

    protected abstract function build();

    protected function buildTree($state) {
        return $this->build();
    }

    protected function isInterestedIn($patch) {
        return false;
    }

    protected function defaultState() {
        return [];
    }
}