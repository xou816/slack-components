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

    protected function patchState($patch) {
        $newState = array_replace($this->getState(), $patch);
        $newRender = $this->buildTree($newState);
        $canReuse = !is_null($this->rendered);
        if (!$canReuse) {
            $patch = $newState;
        }
        if (is_array($newRender)) {
            foreach ($newRender as $key => $new) {
                $old = $canReuse && isset($this->rendered[$key]) ? $this->rendered[$key] : null;
                if (is_array($new)) $new = new ArrayComponent($new);
                if (is_a($new, \Closure::class)) $new = new LazyComponent($new);
                if (is_a($new, AbstractComponent::class)) {
                    $new->restoreState($old, $this->getState());
                    $new->setContext($this->getContext());
                    if ($new->isInterestedIn($patch) || !$canReuse) {
                        $newRender[$key] = $new->patchState($patch);
                    } else {
                        $newRender[$key] = $old;
                    }
                } else {
                    $newRender[$key] = $new;
                }
            }
        }
        $this->state = $newState;
        $this->rendered = $newRender;
        return $this->rendered;
    }

    abstract protected function isInterestedIn($patch);
    abstract protected function defaultState();
    abstract protected function buildTree($state);

}