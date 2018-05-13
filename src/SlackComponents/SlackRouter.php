<?php

namespace SlackComponents;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

class SlackRouter {

	private $client;
	private $options;
	private $handlers = ['now' => [], 'later' => []];

    public function __construct(Client $client, $options) {
		$this->client = $client;
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
					CompiledMessage::compile($channel, $this->handlers[$when][$channel]($payload)) :
					null;
			} catch (\Exception $e) {
				return CompiledMessage::just([
                    'response_type' => 'ephemeral',
                    'replace_original' => false,
                    'text' => $e->getMessage().' ('.$e->getFile().':'.$e->getLine().')'
                ]);
			}
		}
	}

	public function handleNow($payload, $safe = true) {
		$resp = $this->handle('now', $payload, $safe);
		if (is_null($resp)) {
			return CompiledMessage::just([
				'response_type' => 'ephemeral',
				'replace_original' => false,
				'text' => 'Please wait...'
			]);
		} else {
			return $resp;
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

	public function send(CompiledMessage $message) {
		$uri = Psr7\uri_for($this->webhookFor($message->getChannel()));
		$body = Psr7\stream_for(json_encode($message->getMessage()));
		$req = new Psr7\Request('POST', $uri)
			->withHeader('Content-Type', 'application/json')
            ->withBody($body)));
        $this->client->send($req);
		return $this;
	}

	public function handleAndRespond($payload) {
		$res = $this->handleLater($payload);
		if (!is_null($res)) {
			$uri = Psr7\uri_for($payload['response_url']);
			$body = Psr7\stream_for(json_encode($res->getMessage()));
            $req = new Psr7\Request('POST', $uri)
            	->withHeader('Content-Type', 'application/json')
            	->withBody($body);
            $this->client->send($req);
        }
	}
}
