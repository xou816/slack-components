<?php

namespace SlackComponents\Components;

use SlackComponents\Utils\SlackUser;

abstract class ReflectionHandler {

	abstract public function match($action);
	abstract public function map($action);

	public static function createSimple($name, $clazz) {
		return new SimpleHandler($name, $clazz);
	}

	public function build(\Closure $handler) {
		$ref = new \ReflectionFunction($handler);
		return function($payload) use ($handler, $ref) {
            $params = array_map(function(\ReflectionParameter $param) use ($payload) {
				if ($param->getName() === 'payload') {
					return $payload;
				} else if (isset($payload['callback_data'][$param->getName()])) {
					return $payload['callback_data'][$param->getName()];
				} else if ($param->getClass()->isSubclassOf(SlackAction::class)) {
					return $this->getAction($payload);
				} else if ($param->getClass()->isSubclassOf(SlackUser::class)) {
					throw new Exception('Not implemented');
				} else {
					return null;
				}
			}, $ref->getParameters());
			return call_user_func_array($handler, $params);
        };
	}

	private function getAction($payload) {
		$actionToHandle = null;
        foreach ($payload['actions'] as $action) {
            if ($this->match($action)) {
                $actionToHandle = $this->map($action);
                break;
            }
        }
        return $actionToHandle;
	}
}

class SimpleHandler extends ReflectionHandler {

    public function __construct($name, $clazz) {
    	$this->clazz = $clazz;
        $this->name = $name;
    }

    public function match($action) {
        return $action['name'] === $this->name;
    }

    public function map($action) {
    	$clazz = $this->clazz;
        return new $clazz($action);
    }

}