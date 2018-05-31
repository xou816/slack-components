<?php

namespace SlackComponents\Components;

use SlackComponents\Components\CallbackId;
use SlackComponents\Routing\SlackPayload;
use SlackComponents\Interaction\SlackInteraction;
use SlackComponents\Interaction\ReflectionHandler;
use SlackComponents\Interaction\DialogSubmission;

class DialogSubmissionBuilder extends ReflectionHandler {

    public function match($type, $sub) {
        return $type === SlackInteraction::DIALOG;
    }

    public function map($sub) {
        return new DialogSubmission($sub);
    }
}

class Dialog extends AbstractComponent {

    private $elements;
    private $label;
    private $title;
    private $callbackId;

    public function __construct($elements) {
        $this->callbackId = new CallbackId();
        $this->elements = new ArrayComponent($elements);
    }

    public function withElements($elements) {
        $this->elements = new ArrayComponent($elements);
        return $this;
    }

    public function withSubmitLabel($label) {
        $this->label = $label;
        return $this;
    }

    public function withTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function getKeys() {
        return array_filter(array_map(function($component) {
            if (is_a($component, StaticComponent::class)) {
                return $component->getName();
            } else {
                return null;
            }
        }, $this->elements->buildTree([])));
    }

    protected function buildTree($state) {
        $id = isset($state['__dialogCallbackKey']) ?
            $this->callbackId->withKey($state['__dialogCallbackKey']) :
            $this->callbackId;
        return [
            'title' => $this->title,
            'submit_label' => $this->label,
            'elements' => $this->elements,
            'callback_id' => $id
        ];
    }

    protected function isInterestedIn($patch) {
        return true;
    }

    protected function defaultState() {
        return [];
    }

    public function open() {
        return function($payload) {
            $id = CallbackId::read($payload['callback_id']);
            $state = array_replace($id->getData(), [
                '__dialogCallbackKey' => $id->getKey()
            ]);
            $render = $this->patchState($state);
            return SlackPayload::create(SlackPayload::DIALOG, $payload['trigger_id'], $render);
        };
    }

    public function submitted(\Closure $handler) {
        $builder = new DialogSubmissionBuilder();
        return $builder->build($handler);
    }
}