<?php

namespace SlackComponents\Interaction;

use SlackComponents\Components\CallbackId;

abstract class ReflectionHandler {

    private static function getReflection(callable $handler) {
        if (is_a($handler, \Closure::class)) {
            return new \ReflectionFunction($handler);
        } else {
            list($that, $methodName) = $handler;
            return new \ReflectionMethod($that, $methodName);
        }
    }

    public static function call(callable $handler, $payload) {
        $ref = self::getReflection($handler);
        $data = is_a($payload['callback_id'], CallbackId::class) ?
            $payload['callback_id']->getData() : [];
        $interaction = isset($payload['submission']) ? $payload['submission'] :
            (isset($payload['actions']) && count($payload['actions']) > 0 ? $payload['actions'][0] : null);
        $params = $ref->getParameters();
        $l = count($params);
        $params = array_map(function(\ReflectionParameter $param) use ($payload, $interaction, $data, $l) {
            $clazz = $param->getClass();
            $name = $param->getName();
            if ($name === 'payload') {
                return $payload;
            } else if (isset($data[$name])) {
                return $data[$name];
            } else if (!is_null($clazz) && $clazz->isSubclassOf(SlackInteraction::class)
                && is_a($interaction, SlackInteraction::class)) {
                return $interaction;
            } else if (!is_null($clazz) && $clazz->getName() === SlackUser::class
                && is_a($payload['user'], SlackUser::class)) {
                return $payload['user'];
            } else if ($l === 1) {
                return $payload;
            } else {
                return null;
            }
        }, $params);
        return call_user_func_array($handler, $params);
    }
}