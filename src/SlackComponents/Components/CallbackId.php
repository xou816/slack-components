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
        if (is_null($this->key)) {
            return is_null($this->getContext()) ? 
                null : $this->getContext()->getCallbackKey();
        } else {
            return $this->key;
        }
    }
    
    public function build() {
        $arr = ['k' => $this->getKey()];
        if (!is_null($this->data)) {
            $arr['d'] = $this->data;
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
        $default = ['k' => null, 'd' => []];
        $raw = [];
        try {
            $raw = array_replace($default, json_decode(base64_decode($callbackId), true));
        } catch (\Exception $e) {
            $raw = $default;
        }
        $id = new CallbackId();
        return $id
            ->withKey($raw['k'])
            ->withData($raw['d']);
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

    public function merge(CallbackId $id) {
        $prevData = is_null($this->data) ? [] : $this->data;
        return $this
            ->withKey($id->getKey())
            ->withData(array_replace($prevData, $id->getData()));
    }

}