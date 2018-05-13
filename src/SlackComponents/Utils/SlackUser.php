<?php

namespace SlackComponent\Utils;

class SlackUser {

	private $id;
	private $email;
	private $username;
	private $first_name;
	private $last_name;

	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	public function getId() {
		return $this->id;
	}

	public function setEmail($email) {
		$this->email = $email;
		return $this;
	}

	public function getEmail() {
		return $this->email;
	}

	public function setUsername($username) {
		$this->username = $username;
		return $this;
	}

	public function getUsername() {
		return $this->username;
	}

	public function setFirstName($first_name) {
		$this->first_name = $first_name;
		return $this;
	}

	public function getFirstName() {
		return $this->first_name;
	}

	public function setLastName($last_name) {
		$this->last_name = $last_name;
		return $this;
	}

	public function getLastName() {
		return $this->last_name;
	}

	public function getSlackLink() {
		return '<@'.$this->getId().'>';
	}
}
