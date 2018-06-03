<?php

namespace SlackComponents\Components;

abstract class AbstractComponent {

    private $rendered = null;
    private $state = [];
    private $context = null;

    public function getRendered() {
        return $this->rendered;
    }

    protected function setContext($context) {
        $this->context = $context;
    }

    protected function getContext() {
        return $this->context;
    }

    protected function restoreState($rendered, $state) {
        $this->rendered = $rendered;
        $this->state = $state;
    }

    protected function getState() {
        $state = array_replace($this->defaultState(), $this->state);
        $rendered = $this->getRendered();
        if (is_array($rendered) && isset($rendered['callback_id'])) {
            $id = CallbackId::read($rendered['callback_id']);
            return array_replace($state, $id->getData());
        } else {
            return $state;
        }
    }

    private function patchChild($child, $old, $oldState, $patch, $canReuse) {
        if (is_array($child)) $child = new ArrayComponent($child);
        if (is_a($child, \Closure::class)) $child = new LazyComponent($child);
        if (is_a($child, AbstractComponent::class)) {
            $child->restoreState($old, $oldState);
            $child->setContext($this->getContext());
            if ($child->isInterestedIn($patch) || !$canReuse) {
                return $child->patchState($patch);
            } else {
                return $old;
            }
        } else {
            return $child;
        }
    }

    protected function patchState($patch) {
        $oldState = $this->getState();
        $newState = array_replace($oldState, $patch);
        $this->state = $newState;
        $newRender = $this->buildTree($newState);
        $canReuse = !is_null($this->rendered);
        if (!$canReuse) {
            $patch = $newState;
        }
        if (is_array($newRender)) {
            $keys = array_keys($newRender);
            $newChildren = array_map(function($key, $child) use ($oldState, $patch, $canReuse) {
                $old = $canReuse && isset($this->rendered[$key]) ? 
                    $this->rendered[$key] : null;
                return $this->patchChild($child, $old, $oldState, $patch, $canReuse);
            }, $keys, $newRender);
            $newRender = array_combine($keys, $newChildren);
            $newRender = array_filter($newRender, function($child) {
                return !is_null($child);
            });
        } else {
            $old = $canReuse ? $this->rendered : null;
            $newRender = $this->patchChild($newRender, $old, $oldState, $patch, $canReuse);
        }
        $this->rendered = $newRender;
        return $this->rendered;
    }

    abstract protected function isInterestedIn($patch);
    abstract protected function defaultState();
    abstract protected function buildTree($state);

}