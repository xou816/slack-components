<?php

namespace SlackComponents\Components;

use SlackComponents\Interaction\ReflectionHandler;
use SlackComponents\Interaction\ButtonAction;

class Button extends StaticComponent {

    const DANGER = 'danger';
    const DEF = 'default';
    const PRIMARY = 'primary';

    private $value;
    private $name;
    private $text;
    private $style = self::DEF;
    private $confirm = null;

    public function __construct($name, $value = null) {
        $this->name = $name;
        $this->value = is_null($value) ? $name : $value;
    }

    public static function create($name) {
        return new Button($name);
    }

    public function getName() {
        return $this->name;
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

    public function clicked(callable $handler) {
        return function($payload) use ($handler) {
            if (isset($payload['actions']) && count($payload['actions']) > 0) {
                $action = $payload['actions'][0];
                if (is_a($action, ButtonAction::class) && $action->getName() === $this->name) {
                    return ReflectionHandler::call($handler, $payload);
                }
            }
            return null;
        };
    }
}