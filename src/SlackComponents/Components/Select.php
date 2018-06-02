<?php

namespace SlackComponents\Components;

use SlackComponents\Interaction\ReflectionHandler;
use SlackComponents\Interaction\SelectAction;

class Select extends StaticComponent {

    private $name;
    private $text;
    private $style = Style::DEF;
    private $options = [];
    private $builder;

    public function __construct($name) {
        $this->name = $name;
        $this->builder = ReflectionHandler::createSimple($name, SelectAction::class);
    }

    public static function name($name) {
        return new Select($name);
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

    public function getName() {
        return $this->name;
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
        return $this->builder->build($handler);
    }
}