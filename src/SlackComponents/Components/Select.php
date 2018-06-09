<?php

namespace SlackComponents\Components;

use SlackComponents\Interaction\ReflectionHandler;
use SlackComponents\Interaction\SelectAction;

class Select extends StaticComponent {

    private $name;
    private $text;
    private $options = [];

    public function __construct($name) {
        $this->name = $name;
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
        return function($payload) use ($handler) {
            if (isset($payload['actions']) && count($payload['actions']) > 0) {
                $action = $payload['actions'][0];
                if (is_a($action, SelectAction::class) && $action->getName() === $this->name) {
                    return ReflectionHandler::call($handler, $payload);
                }
            }
            return null;
        };
    }
}