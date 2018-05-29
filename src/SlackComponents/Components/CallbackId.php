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
		return $this;
	}

	public function withKey($key) {
		$this->key = $key;
		return $this;
	}

	public function getKey() {
		$fromContext = is_null($this->getContext()) ? 
			null : $this->getContext()->getCallbackKey();
		return is_null($this->key) ?
			 $fromContext : null;
	}
	
	public function build() {
		$arr = ['key' => $this->getKey()];
		if (!is_null($data)) {
			$arr['data'] = $data;
		}
		return base64_encode(json_encode($arr));
	}

	public static function read($callbackId) {
		try {
            return json_decode(base64_decode($callbackId), true);
        } catch (\Exception $e) {
            return ['key' => null, 'data' => []];
        }
	}

}