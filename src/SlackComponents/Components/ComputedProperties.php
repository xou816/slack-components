<?php

namespace SlackComponents\Components;

trait ComputedProperties {

    private $computed;
    private $computers;

    public function __set($key, $value) {
        if (property_exists($this, $key) && isset($this->computers[$key])) {
            $this->computed[$key] = $value;
        } else if (is_a($value, \Closure::class)) {
            $this->computers[$key] = $value->bindTo($this);
        } else {
            $this->$key = $value;
        }
    }

    public function __get($key) {
        if (!isset($this->computed[$key])) {
            $state = $this->getState();
            $closure = $this->computers[$key]->bindTo($this);
            $ref = new \ReflectionFunction($closure);
            $params = $ref->getParameters();
            $l = count($params);
            $params = array_map(function(\ReflectionParameter $param) use ($state, $l) {
                $name = $param->getName();
                if (isset($state[$name])) {
                    return $state[$name];
                } else if ($l === 1) {
                    return $state;
                }
            }, $ref->getParameters());
            $this->computed[$key] = call_user_func_array($closure, $params); 
        }
        return $this->computed[$key];
    }

}