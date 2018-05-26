<?php

namespace SlackComponents;

use SlackComponents\Components\AbstractComponent;
use SlackComponents\CompiledResource;

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

	public function build($channel, $data) {
	    $res = $this->patchState($data);
	    return CompiledResource::compileMessage($channel, $res);
	}

	public function send(CompiledResource $res) {
	    return $this->router->send($res);
    }

    public function buildAndSend($channel, $args = null) {
	    $this->router->send($this->build($channel, $args));
    }

	protected function when(\Closure $handler, $channel) {
	    $this->router->now($channel, function($payload) use ($handler, $channel) {
	        $this->restoreState($payload['original_message'], $payload['callback_data']);
	        $resp = $handler($payload);
	        return is_a($resp, CompiledResource::class) ? $resp :
	        	CompiledResource::compileResponse($channel, $payload['response_url'], $handler($payload));
        });
	    return $this;
    }

    protected function after(\Closure $handler, $channel) {
        $this->router->later($channel, function($payload) use ($handler, $channel) {
            $this->restoreState($payload['original_message'], $payload['callback_data']);
            $resp = $handler($payload);
            return is_a($resp, CompiledResource::class) ? $resp :
            	CompiledResource::compileResponse($channel, $payload['response_url'], $handler($payload));
        });
        return $this;
    }

	protected abstract function buildMessage($state);

	protected function buildTree($state) {
		$ref = new \ReflectionMethod($this, 'buildMessage');
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
		return call_user_func_array([$this, 'buildMessage'], $params);
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