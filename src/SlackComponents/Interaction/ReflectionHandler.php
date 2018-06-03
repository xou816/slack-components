<?php

namespace SlackComponents\Interaction;

use SlackComponents\Utils\SlackUser;

abstract class ReflectionHandler {

	abstract public function match($type, $interaction);
	abstract public function map($interaction);

	public static function createSimple($name, $clazz) {
		return new SimpleActionHandler($name, $clazz);
	}

	public function build(\Closure $handler) {
		$ref = new \ReflectionFunction($handler);
		return function($payload) use ($handler, $ref) {
			$data = $payload['callback_id']->getData();
			$interaction = $this->getInteraction($payload);
			if (is_null($interaction)) {
				return null;
			} else {
				$params = $ref->getParameters();
				$l = count($params);
	            $params = array_map(function(\ReflectionParameter $param) use ($payload, $interaction, $data, $l) {
	            	$clazz = $param->getClass();
	            	$name = $param->getName();
					if ($name === 'payload') {
						return $payload;
					} else if (isset($data[$name])) {
						return $data[$name];
					} else if (!is_null($clazz) && $clazz->isSubclassOf(SlackInteraction::class)) {
						return $interaction;
					} else if (!is_null($clazz) && $clazz->getName() === SlackUser::class) {
						$user = new SlackUser();
						return $user
							->setId($payload['user']['id'])
							->setUsername($payload['user']['name']);
					} else if ($l === 1) {
						return $payload;
					} else {
						return null;
					}
				}, $params);
				return call_user_func_array($handler, $params);
			}
        };
	}

	private function getInteraction($payload) {
		$toHandle = null;
		$type = $payload['type'];
		if ($type === SlackInteraction::MESSAGE) {		
	        foreach ($payload['actions'] as $action) {
	            if ($this->match($type, $action)) {
	                $toHandle = $this->map($action);
	                break;
	            }
	        }
		} else if ($type === SlackInteraction::DIALOG) {
			$sub = $payload['submission'];
			if ($this->match($type, $sub)) {
				$toHandle = $this->map($sub);
			}
		}
        return $toHandle;
	}
}

class SimpleActionHandler extends ReflectionHandler {

    public function __construct($name, $clazz) {
    	$this->clazz = $clazz;
        $this->name = $name;
    }

    public function match($type, $action) {
        return $type === SlackInteraction::MESSAGE && $action['name'] === $this->name;
    }

    public function map($action) {
    	$clazz = $this->clazz;
        return new $clazz($action);
    }

}