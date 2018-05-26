<?php

namespace SlackComponents\Components;

class Button extends StaticComponent {

    private $value;
    private $name;
    private $text;
    private $style = Style::DEF;
    private $confirm = null;
    private $builder;

    public function __construct($name, $value = null) {
        $this->name = $name;
        $this->value = is_null($value) ? $name : $value;
        $this->builder = ReflectionHandler::createSimple($name, ButtonAction::class);
    }

    public function withStyle($style) {
        $this->style = $style;
        return $this;
    }

    public function withLabel($text) {
        $this->text = $text;
        return $this;
    }

    public function withConfirmation($title, $message, $confirm = 'Confirm', $cancel = 'Cancel') {
        $this->confirm =  [
            'title' => $title,
            'text' => $message,
            'ok_text' => $confirm,
            'dismiss_text' => $cancel
        ];
        return $this;
    }

    public function build() {
        $res = [
            'type' => 'button',
            'style' => $this->style,
            'value' => $this->value,
            'name' => $this->name,
            'text' => $this->text
        ];
        if (!is_null($this->confirm)) {
            $res['confirm'] = $this->confirm;
        }
        return $res;
    }

    public function clicked(\Closure $handler) {
        return $this->builder->build($handler);
    }
}