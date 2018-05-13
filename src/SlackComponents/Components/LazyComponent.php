<?php

namespace SlackComponents\Components;

class LazyComponent extends AbstractComponent {

    private $subscribed;
    private $closure;
    private $ref;

    public function __construct(\Closure $closure) {
        $this->closure = $closure;
        $this->ref = new \ReflectionFunction($closure);
        $this->subscribed = array_map(function(\ReflectionParameter $param) {
            return $param->getName();
        }, $this->ref->getParameters());
    }

    protected function isInterestedIn($patch) {
        return array_reduce(array_keys($patch), function($prev, $key) {
            return $prev || in_array($key, $this->subscribed);
        }, false);
    }

    protected function buildTree($patch) {
        $patch = array_replace($this->getState(), array_intersect_key($patch, array_flip($this->subscribed)));
        $params = array_map(function($param) use ($patch) {
            return isset($patch[$param]) ? $patch[$param] : null;
        }, $this->subscribed);
        return call_user_func_array($this->closure->bindTo($this->getContext()), $params);
    }

    protected function defaultState() {
        return [];
    }
}