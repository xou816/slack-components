<?php

namespace SlackComponents\Components;

class Textarea extends StaticComponent {

    const EMAIL = 'email';
    const NUMBER = 'number';
    const TEL = 'tel';
    const URL = 'url';

    private $label;
    private $name;
    private $placeholder;
    private $subtype = null;
    private $min;
    private $max;
    private $value;
    private $hint;

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

    public function withLength($min, $max) {
        $this->min = $min;
        $this->max = $max;
        return $this;
    }

    public function withValue($value) {
        $this->value = $value;
        return $this;
    }

    public function withHint($hint) {
        $this->hint = $hint;
        return $this;
    }

    public function build() {
        return [
            'type' => 'textarea',
            'label' => $this->label,
            'name' => $this->name,
            'subtype' => $this->subtype,
            'min_length' => $this->min,
            'max_length' => $this->max,
            'value' => $this->value,
            'hint' => $this->hint
        ];
    }
}