<?php

namespace SlackComponents\Components;

use SlackComponents\CompiledResource;
use SlackComponents\CallbackId;

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

    public function __construct($elements) {
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

    protected function buildTree($state) {
        return [
            'title' => $this->title,
            'submit_label' => $this->label,
            'elements' => $this->elements->patchState($state)
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
            $render = $this->buildTree($payload['callback_data']);
            $decoded = CallbackId::read($payload['callback_id']);
            return CompiledResource::compileDialog($decoded['channel'], $payload['trigger_id'], $render);
        };
    }

    public function submitted(\Closure $handler) {
        $builder = new DialogSubmissionBuilder();
        return $builder->build($handler);
    }
}