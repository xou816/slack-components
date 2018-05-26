<?php

namespace SlackComponents\Components;

class ButtonAction implements SlackInteraction {

	private $value;
	private $name;
	private $raw;

	public function __construct($action) {
		$this->raw = $action;
		$this->value = isset($action['value']) ? $action['value'] : null;
		$this->name = isset($action['name']) ? $action['name'] : null;
	}

	public function getRaw() {
		return $this->raw;
	}

	public function getType() {
		return SlackInteraction::MESSAGE;
	}

	public function getValue() {
		return $this->value;
	}

	public function getName() {
		return $this->name;
	}

}