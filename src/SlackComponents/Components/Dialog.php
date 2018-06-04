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

    public function __construct($title = 'Dialog') {
        $this->callbackId = new CallbackId();
        $this->title = $title;
    }

    public static function create($title) {
        return new Dialog($title);
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

    public function withCallbackId(CallbackId $id) {
        $this->callbackId = $id;
        return $this;
    }

    protected function getContext() {
        return $this;
    }

    public function getCallbackKey() {
        return $this->callbackId->getKey();
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
        return [
            'title' => $this->title,
            'submit_label' => $this->label,
            'elements' => $this->elements,
            'callback_id' => $this->callbackId
        ];
    }

    protected function isInterestedIn($patch) {
        return true;
    }

    protected function defaultState() {
        return [];
    }

    public function open($payload) {
        $this->callbackId = $this->callbackId->merge(CallbackId::read($payload['callback_id']));
        $render = $this->patchState($this->callbackId->getData());
        return SlackPayload::create(SlackPayload::DIALOG, $payload['trigger_id'], $render);
    } 

    public function doOpen() {
        return function($payload) {
            return $this->open($payload);
        };
    }

    public function submitted(callable $handler) {
        $builder = new DialogSubmissionBuilder();
        return $builder->build($handler);
    }
}