<?php

namespace SlackComponents\Components;

use SlackComponents\Interaction\ReflectionHandler;
use SlackComponents\Interaction\SelectAction;

class Select extends StaticComponent {

    private $name;
    private $text;
    private $options = [];
    private $builder;

    public function __construct($name) {
        $this->name = $name;
        $this->builder = ReflectionHandler::createSimple($name, SelectAction::class);
    }

    public static function create($name) {
        return new Select($name);
    }


    public function build() {
        return [
            'type' => 'select',
            'name' => $this->name,
            'text' => $this->text,
            'options' => array_map(function($el) {
                return [
                    $this->textOrLabel() => $el['text'],
                    'value' => $el['value']
                ];
            }, $this->options)
        ];
    }

    public function getName() {
        return $this->name;
    }

    public function withLabel($text) {
        $this->text = $text;
        return $this;
    }

    private function textOrLabel() {
        if (get_class($this->getContext()) === Dialog::class) {
            return 'label';
        } else {
            return 'text';
        }
    }

    public function withOption($value, $text) {
        $this->options[] = ['text' => $text, 'value' => $value];
        return $this;
    }

    public function selected(callable $handler) {
        return $this->builder->build($handler);
    }
}