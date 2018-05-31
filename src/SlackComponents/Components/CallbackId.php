<?php

namespace SlackComponents\Components;

class CallbackId extends StaticComponent {

	private $key = null;
	private $data = null;

	public function __construct($key = null) {
		$this->key = $key;
	}

	public function withData($data) {
		$this->data = $data;
		return $this;
	}

	public function getData() {
		return $this->data;
	}

	public function withKey($key) {
		$this->key = $key;
		return $this;
	}

	public function getKey() {
		$fromContext = is_null($this->getContext()) ? 
			null : $this->getContext()->getCallbackKey();
		return is_null($this->key) ?
			 $fromContext : $this->key;
	}
	
	public function build() {
		$arr = ['key' => $this->getKey()];
		if (!is_null($this->data)) {
			$arr['data'] = $this->data;
		}
		return base64_encode(json_encode($arr));
	}

	public function __toString() {
		return $this->build();
	}

	public static function read($callbackId) {
		if (is_a($callbackId, CallbackId::class)) {
        	return $callbackId;
        }
		$default = ['key' => null, 'data' => []];
		$raw = [];
		try {
            $raw = array_replace($default, json_decode(base64_decode($callbackId), true));
        } catch (\Exception $e) {
            $raw = $default;
        }
        $id = new CallbackId();
        return $id
            ->withKey($raw['key'])
            ->withData($raw['data']);
	}

	public static function just($key) {
		$id = new CallbackId();
        return $id
          	->withKey($key)
          	->build();
	}

	public static function wrap($data = null) {
		$id = new CallbackId();
        return $id
          	->withData($data);
    	}

}