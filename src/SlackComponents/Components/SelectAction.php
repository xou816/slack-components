<?php

namespace SlackComponents\Components;

class SelectAction implements SlackAction {

	private $selected_options;
	private $name;
	private $raw;

	public function __construct($action) {
		$this->raw = $action;
		$this->selected_options = isset($action['selected_options']) ?
			array_map(function($o) {
				return $o;
			}, $action['selected_options']) :
			null;
		$this->name = isset($action['name']) ? $action['name'] : null;
	}

	public function getRaw() {
		return $this->raw;
	}

	public function getSelectedOptions() {
		return $this->selected_options;
	}

	public function getValue() {
	    return $this->selected_options[0]['value'];
    }

	public function getName() {
		return $this->name;
	}

}