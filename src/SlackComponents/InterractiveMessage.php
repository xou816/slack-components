<?php

namespace API\Infrastructure\Slack;

use API\Infrastructure\Slack\Components\AbstractComponent;

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
	    return CompiledMessage::compile($channel, $res);
	}

	public function send(CompiledMessage $message) {
	    return $this->router->send($message);
    }

    public function buildAndSend($channel, $args = null) {
	    $this->router->send($this->build($channel, $args));
    }

	protected function when(\Closure $handler, $channel) {
	    $this->router->now($channel, function($payload) use ($handler) {
	        $this->restoreState($payload['original_message'], $payload['callback_data']);
	        return $handler($payload);
        });
	    return $this;
    }

    protected function after(\Closure $handler, $channel) {
        $this->router->later($channel, function($payload) use ($handler) {
            $this->restoreState($payload['original_message'], $payload['callback_data']);
            return $handler($payload);
        });
        return $this;
    }

	protected abstract function buildMessage($state);

	protected function buildTree($state) {
        return $this->buildMessage($state);
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