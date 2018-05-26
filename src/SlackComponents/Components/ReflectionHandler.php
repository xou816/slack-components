<?php

namespace SlackComponents\Components;

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
			$interaction = $this->getInteraction($payload);
			if (is_null($interaction)) {
				return null;
			} else {	
	            $params = array_map(function(\ReflectionParameter $param) use ($payload, $interaction) {
					if ($param->getName() === 'payload') {
						return $payload;
					} else if (isset($payload['callback_data'][$param->getName()])) {
						return $payload['callback_data'][$param->getName()];
					} else if ($param->getClass()->isSubclassOf(SlackInteraction::class)) {
						return $interaction;
					} else if ($param->getClass()->isSubclassOf(SlackUser::class)) {
						throw new Exception('Not implemented');
					} else {
						return null;
					}
				}, $ref->getParameters());
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