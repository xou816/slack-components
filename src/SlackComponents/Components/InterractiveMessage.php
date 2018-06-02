<?php

namespace SlackComponents\Components;

use SlackComponents\Routing\SlackPayload;
use SlackComponents\Routing\SlackRouter;


abstract class InterractiveMessage extends AbstractComponent {

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

	public function getCallbackKey() {
		return get_class($this);
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

    private function createResponse($payload, \Closure $handler) {
    	if (isset($payload['original_message'])) {
	        $this->restoreState($payload['original_message'], $payload['callback_id']->getData());
	    }
	    $resp = $handler($payload);
	    if (is_null($resp)) {
	    	return null;
	    } else {
	    	return is_a($resp, SlackPayload::class) ? $resp : 
	    		SlackPayload::create(SlackPayload::RESPONSE, $payload['response_url'], $resp);
	    }	    		
    }

    protected function callback($data) {
    	return CallbackId::wrap($data)
    		->withKey($this->getCallbackKey());
    }

	protected function when(\Closure $handler, $callbackKey = null) {
		$callbackKey = is_null($callbackKey) ? $this->getCallbackKey() : $callbackKey;
	    $this->router->when($callbackKey, function($payload) use ($handler) {
	        return $this->createResponse($payload, $handler);
        });
	    return $this;
    }

    protected function after(\Closure $handler, $callbackKey = null) {
    	$callbackKey = is_null($callbackKey) ? $this->getCallbackKey() : $callbackKey;
        $this->router->when($callbackKey, function($payload) use ($handler, $callbackKey) {
	        $resp = $this->createResponse($payload, $handler);
	        if (!is_null($resp)) {
	        	return $resp->withType(SlackPayload::RESPONSE_DEFER);
	        } else {
	        	return $resp;
	        }
        });
	    return $this;
    }

	protected function __buildMessage() {
		return 'buildMessage';
	}

	protected function buildTree($state) {
		$ref = new \ReflectionMethod($this, $this->__buildMessage());
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
		return call_user_func_array([$this, $this->__buildMessage()], $params);
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