<?php

namespace SlackComponents\Components;

use SlackComponents\Routing\SlackPayload;
use SlackComponents\Routing\SlackRouter;


abstract class InteractiveMessage extends AbstractComponent {

    use ComputedProperties;

    public static function slackLink($target, $name = null) {
        return '<'.$target.(!is_null($name) ? '|'.$name : '').'>';
    }

    public static function linkToMessage($payload, $display) {
        return self::slackLink('https://'.$payload['team']['domain'].'.slack.com/archives/'.$payload['channel']['id'].'/'.'p'.str_replace('.', '', $payload['message_ts']), $display);
    }

    private $router;

    public function __construct(SlackRouter $router) {
        $this->router = $router;
    }

    public static function create(SlackRouter $router, \Closure $c) {
        return new AnonymousMessage($router, $c);
    }

    public function getCallbackKey() {
        return substr(sha1(get_class($this)), 0, 6);
    }

    public function build($channel, $data) {
        $res = $this->patchState($data);
        return SlackPayload::create(SlackPayload::WEBHOOK, $channel, $res);
    }

    public function send(SlackPayload $res) {
        return $this->router->send($res);
    }

    public function buildAndSend($channel, $args = null) {
        $this->router->send($this->build($channel, $args));
    }

    private function wrap(\Closure $handler) {
        return function($payload) use ($handler) {      
            if (isset($payload['original_message'])) {
                $this->restoreState($payload['original_message'], $payload['callback_id']->getData());
            } else {
                $this->restoreState(null, $payload['callback_id']->getData());
            }
            $resp = $handler($payload);
            if (is_null($resp)) {
                return null;
            } else {
                return is_a($resp, SlackPayload::class) ? $resp : 
                    SlackPayload::create(SlackPayload::RESPONSE, $payload['response_url'], $resp);
            }               
        };
    }

    protected function callback($data) {
        return CallbackId::wrap($data)
            ->withKey($this->getCallbackKey());
    }

    public function when(\Closure $handler, $callbackKey = null) {
        $callbackKey = is_null($callbackKey) ? $this->getCallbackKey() : $callbackKey;
        $wrapped = $this->wrap($handler);
        $this->router->when($callbackKey, $wrapped);
        return $this;
    }

    protected function __buildMessage() {
        $ref = new \ReflectionMethod($this, 'buildMessage');
        $ref->setAccessible(true);
        return $ref;
    }

    protected function buildTree($state) {
        $ref = $this->__buildMessage();
        $params = $ref->getParameters();
        $l = count($params);
        $params = array_map(function(\ReflectionParameter $param) use ($state, $l) {
            $name = $param->getName();
            if (isset($state[$name])) {
                return $state[$name];
            } else if ($l === 1) {
                return $state;
            }
        }, $ref->getParameters()); 
        return is_a($ref, \ReflectionMethod::class) ?
            $ref->invokeArgs($this, $params) :
            $ref->invokeArgs($params);
    }

    protected function defaultState() {
        return [];
    }

    protected function isInterestedIn($patch) {
        return false;
    }

    protected function getContext() {
        return $this;
    }
}

class AnonymousMessage extends InteractiveMessage {

    protected $builder;
    protected $key = AnonymousMessage::class;

    public function __construct(SlackRouter $router, \Closure $c) {
        parent::__construct($router);
        $this->builder = $c;
    }

    public function getCallbackKey() {
        return $this->key;
    }

    public function withCallbackKey($key) {
        $this->key = $key;
        return $this;
    }

    protected function __buildMessage() {
        $ref = new \ReflectionFunction($this->builder);
        return $ref;
    }

    public function patchState($patch) {
        return parent::patchState($patch);
    }

}