<?php

namespace SlackComponents\Components;

class DialogSubmission extends SlackInteraction {
    
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
}