<?php

namespace SlackComponents\Routing;

class SlackPayload {

	const WEBHOOK = 'webhook';
	const RESPONSE = 'response_url';
    const RESPONSE_DEFER = 'response_defer';
	const DIALOG = 'trigger_id';

    private $type;
    private $details;
    private $payload;

    public static function create($type, $details, $payload) {
        $p = new SlackPayload();
        return $p
            ->withType($type)
            ->withDetails($details)
            ->withPayload($payload);
    }

    public function getType() {
        return $this->type;
    }

    public function withType($type) {
        $this->type = $type;
        return $this;
    }

    public function getDetails() {
        return $this->details;
    }

    public function withDetails($details) {
        $this->details = $details;
        return $this;
    }

    public function getPayload() {
        return $this->payload;
    }

    public function withPayload($payload) {
        $this->payload = $payload;
        return $this;
    }
}