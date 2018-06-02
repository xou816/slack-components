<?php

namespace SlackComponents\Components;

class Textarea extends StaticComponent {

    private $label;
    private $name;
    private $placeholder;
    private $subtype = null;
    private $value;

    public function __construct($name, $label = null) {
        $this->name = $name;
        $this->label = is_null($label) ? $name : $label;
    }

    public static function create($name) {
        return new TextInput($name);
    }

    public function getName() {
        return $this->name;
    }

    public function withLabel($label) {
        $this->label = $label;
        return $this;
    }

    public function withPlaceholder($placeholder) {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function withValue($value) {
        $this->value = $value;
        return $this;
    }

    public function build() {
        return [
            'type' => 'textarea',
            'label' => $this->label,
            'name' => $this->name,
            'value' => $this->value
        ];
    }
}