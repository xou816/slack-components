<?php

namespace SlackComponents;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use SlackComponents\Utils\ApiClient;

class SlackRouter {

	private $client;
	private $api;
	private $options;
	private $handlers = ['now' => [], 'later' => []];

    public function __construct(Client $client, ApiClient $api, $options) {
		$this->client = $client;
		$this->api = $api;
		$this->options = $options;
	}

	private function handle($when, $payload, $safe = true) {
		if ($safe && $payload['token'] != $this->options['token']) {
			throw new SlackRouterException('Token mismatch');
		} else {
            $res = CallbackId::read($payload['callback_id']);
            $payload['callback_data'] = $res['data'];
            try {
			    $channel = $res['channel'];
				return isset($this->handlers[$when][$channel]) ?
					$this->handlers[$when][$channel]($payload) :
					null;
			} catch (\Exception $e) {
				return CompiledResource::just([
                    'response_type' => 'ephemeral',
                    'replace_original' => false,
                    'text' => $e->getMessage().' ('.$e->getFile().':'.$e->getLine().')'
                ]);
			}
		}
	}

	public function handleNow($payload, $safe = true) {
		$res = $this->handle('now', $payload, $safe);
		if (is_null($res)) {
			return CompiledResource::just([
				'response_type' => 'ephemeral',
				'replace_original' => false,
				'text' => 'Please wait...'
			]);
		} else {
			return $res;
		}
	}

	public function handleLater($payload, $safe = true) {
		return $this->handle('later', $payload, $safe);
	}

	private function mergeHandler($when, $channel, $next) {
		if (isset($this->handlers[$when][$channel])) {
			$curr = $this->handlers[$when][$channel];
			return function($payload) use ($curr, $next) {
				$res = $curr($payload);
				return !is_null($res) ? $res : $next($payload);
			};
		} else {
			return $next;
		}
	}

	public function now($channels, \Closure $handler) {
        foreach (explode("|", $channels) as $channel) {
            $this->handlers['now'][$channel] = $this->mergeHandler('now', $channel, $handler);
        }
		return $this;
	}

	public function later($channels, \Closure $handler) {
        foreach (explode("|", $channels) as $channel) {
            $this->handlers['later'][$channel] = $this->mergeHandler('later', $channel, $handler);
        }
		return $this;
	}

	private function webhookFor($channel) {
		$channel = explode('/', $channel)[0];
		if (isset($this->options['webhooks'][$channel])) {
			return $this->options['webhooks'][$channel];
		} else {
			throw new SlackRouterException('No webhook for '.$channel);
		}
	}

	private function sendJson($uri, $body) {
		$body = Psr7\stream_for(json_encode($body));
		$req = new Psr7\Request('POST', Psr7\uri_for($uri));
		$req = $req
			->withHeader('Content-Type', 'application/json')
	        ->withBody($body);
	    $this->client->send($req);
	}

	public function send(CompiledResource $resource) {
		$transport = $resource->getTransport();
		if ($transport['type'] === ResourceTransport::TRIGGER) {
			$this->api->dialogOpen([
				'trigger_id' => $transport['value'],
				'dialog' => json_encode($resource->getResource())
			]);
			return $this;
		} else if ($transport['type'] === ResourceTransport::WEBHOOK) {		
			$uri = $this->webhookFor($transport['value']);
			$this->sendJson($uri, $resource->getResource());
			return $this;
		} else if ($transport['type'] === ResourceTransport::RESPONSE_URL) {
			$uri = $transport['value'];
			$this->sendJson($uri, $resource->getResource());
		} else {
			throw new SlackRouterException('Cannot send resource of type '.$transport['type']);
		}
	}

	public function handleAndRespond($payload) {
		$res = $this->handleLater($payload);
		if (!is_null($res)) {
			$this->send($res);
        }
	}
}
