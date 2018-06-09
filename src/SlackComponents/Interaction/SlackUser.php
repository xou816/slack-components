<?php

namespace SlackComponents\Interaction;

use SlackComponents\Routing\SlackPayload;

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

    public function sendMessage($message) {
        return SlackPayload::create(SlackPayload::POST, $this->getId(), $message);
    }
}
