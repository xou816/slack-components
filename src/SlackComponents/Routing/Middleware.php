<?php

namespace SlackComponents\Routing;

use SlackComponents\Components\CallbackId;
use SlackComponents\Interaction\ButtonAction;
use SlackComponents\Interaction\DialogSubmission;
use SlackComponents\Interaction\SelectAction;
use SlackComponents\Interaction\SlackInteraction;
use SlackComponents\Interaction\SlackUser;

class Middleware {

    public static function parseCallbacks() {
        return function($payload, $next) {
            $payload['callback_id'] = CallbackId::read($payload['callback_id']);
            return $next($payload);
        };
    }

    public static function parseInteractions() {
        return function($payload, $next) {
            if (isset($payload['actions'])) {
                $payload['actions'] = array_map(function($action) {
                    $actionType = $action['type'];
                    switch ($actionType) {
                        case 'button':
                            return new ButtonAction($action);
                        case 'select':
                            return new SelectAction($action);
                    }
                }, $payload['actions']);
            } else if (isset($payload['submission'])) {
                $payload['submission'] = new DialogSubmission($payload['submission']);
            }
            return $next($payload);
        };
    }

    public static function parseUser() {
        return function($payload, $next) {
            $user = new SlackUser();
            $payload['user'] = $user
                ->setId($payload['user']['id'])
                ->setUsername($payload['user']['name']);
            return $next($payload);
        };
    }

}