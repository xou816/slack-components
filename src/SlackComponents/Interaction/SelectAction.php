<?php

namespace SlackComponents\Interaction;

class SelectAction implements SlackInteraction {

	private $selected_options;
	private $name;
	private $raw;

	public function __construct($action) {
		$this->raw = $action;
		$this->selected_options = isset($action['selected_options']) ?
			$action['selected_options'] :
			[];
		$this->name = isset($action['name']) ? $action['name'] : null;
	}

	public function getRaw() {
		return $this->raw;
	}

	public function getType() {
		return SlackInteraction::MESSAGE;
	}

	public function getSelectedOptions() {
		return array_map(function($selected) {
			return $selected['value'];
		}, $this->selected_options);
	}

	public function getValue() {
	    return count($this->selected_options) > 0 ? 
	    	$this->selected_options[0]['value'] :
	    	null;
    }

	public function getName() {
		return $this->name;
	}

}