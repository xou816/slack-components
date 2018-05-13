<?php

namespace SlackComponents\Components;

class Select {

    private $name;
    private $text;
    private $style = Style::DEF;
    private $options = [];

    public function __construct($name) {
        $this->name = $name;
    }

    public function build() {
        return [
            'type' => 'select',
            'name' => $this->name,
            'text' => $this->text,
            'style' =>  $this->style,
            'options' => $this->options
        ];
    }

    public function withLabel($text) {
        $this->text = $text;
        return $this;
    }

    public function withStyle($style) {
        $this->style = $style;
        return $this;
    }

    public function withOption($value, $text) {
        $this->options[] = ['text' => $text, 'value' => $value];
        return $this;
    }

    public function selected(\Closure $handler) {
        return function($payload) use ($handler) {
            $actionToHandle = null;
            foreach ($payload['actions'] as $action) {
                if ($action['name'] === $this->name) {
                    $actionToHandle = new SelectAction($action);
                    break;
                }
            }
            if (!is_null($actionToHandle)) {
                return $handler($actionToHandle, $payload);
            } else {
                return null;
            }
        };
    }
}