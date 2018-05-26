<?php

namespace SlackComponents\Components;

class TextInput extends StaticComponent {

    private $label;
    private $name;
    private $placeholder;
    private $subtype = null;

    public function __construct($name, $label = null) {
        $this->name = $name;
        $this->value = is_null($label) ? $name : $label;
    }

    public function withLabel($label) {
        $this->label = $label;
        return $this;
    }

    public function withPlaceholder($placeholder) {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function build() {
        return [
            'type' => 'text',
            'label' => $this->label,
            'name' => $this->name
        ];
    }
}