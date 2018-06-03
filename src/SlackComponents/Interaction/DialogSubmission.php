<?php

namespace SlackComponents\Interaction;

class DialogSubmission implements SlackInteraction {
    
	private $raw;

	public function __construct($raw) {
		$this->raw = $raw;
	}

    public function getRaw() {
    	return $this->raw;
    }

    public function getType() {
    	return SlackInteraction::DIALOG;
    }

    public function __get ($key) {
        return $this->raw[$key];
    }

    public function __set($key, $value) {
        $this->raw[$key] = $value;
    }

    public function __isset ($key) {
        return isset($this->raw[$key]);
    }

    public function __unset($key) {
        unset($this->raw[$key]);
    }
}