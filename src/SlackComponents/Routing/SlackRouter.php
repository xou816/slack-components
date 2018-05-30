<?php

namespace SlackComponents\Routing;

use GuzzleHttp\Client;
use SlackComponents\Components\CallbackId;

class SlackRouter {

	private $client;
	private $options;
	private $handlers = [];

    public function __construct(Client $client, $options) {
    	$options = array_replace([
    		'safe' => true
    	], $options);
		$this->client = new SlackClient($client, $options);
		$this->options = $options;
	}

	public function handle($payload) {
		if ($this->options['safe'] && $payload['token'] != $this->options['token']) {
			throw new SlackRouterException('Token mismatch');
		} else {
           	$id = CallbackId::read($payload['callback_id']);
           	$payload['callback_id'] = $id;
            try {
   				return isset($this->handlers[$id->getKey()]) ?
					$this->handlers[$id->getKey()]($payload) :
					null;
			} catch (\Exception $e) {
				return null;
			}
		}
	}

	private function mergeHandler($callbackKey, $next) {
		if (isset($this->handlers[$callbackKey])) {
			$curr = $this->handlers[$callbackKey];
			return function($payload) use ($curr, $next) {
				$res = $curr($payload);
				return !is_null($res) ? $res : $next($payload);
			};
		} else {
			return $next;
		}
	}

	public function when($callbackKey, \Closure $handler) {
        $this->handlers[$callbackKey] = $this->mergeHandler($callbackKey, $handler);
		return $this;
	}

	public function hookBeforeResponse($payload) {
		$res = $this->handle($payload);
		if (!is_null($res) && $payload->getType() === SlackPayload::RESPONSE) {
			return $res->getPayload();
		}
	}

	public function hookAfterResponse($payload) {
		$res = $this->handle($payload);
		if (!is_null($res)) {
			$this->client->send($res);
        }
	}

	public function send(SlackPayload $payload) {
		$this->client->send($payload);
	}
}
