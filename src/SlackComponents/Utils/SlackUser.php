<?php

namespace SlackComponents\Utils;

class SlackUser {

	private $id;
	private $username;

	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	public function getId() {
		return $this->id;
	}

	public function setUsername($username) {
		$this->username = $username;
		return $this;
	}

	public function getUsername() {
		return $this->username;
	}

	public function getMention() {
		return '<@'.$this->getId().'>';
	}
}
