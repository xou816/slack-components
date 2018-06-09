<?php

namespace SlackComponents\Routing;

use GuzzleHttp\Client;

class SlackRouter {

    private $client;
    private $options;
    private $middlewares = [];
    private $handlers = [];

    public function __construct(Client $client, $options = []) {
        $options = array_replace([
            'token' => 'INVALID_TOKEN',
            'app_token' => 'INVALID_TOKEN'
        ], $options);
        $this->client = new SlackClient($client, $options);
        $this->options = $options;
    }

    public static function defaults(Client $client, $options = []) {
        $router = new SlackRouter($client, $options);
        return $router
            ->push($router->checkToken())
            ->push(Middleware::parseCallbacks())
            ->push(Middleware::parseInteractions())
            ->push(Middleware::parseUser());
    }

    public function checkToken() {
        return function($payload, $next) {
            if ($payload['token'] != $this->options['token']) {
                throw new SlackRouterException('Token mismatch');
            } else {
                return $next($payload);
            }
        };
    }

    private function next($funs) {
        return function($payload) use ($funs) {
            if (count($funs) > 0) {
                $fun = array_shift($funs);
                return $fun($payload, $this->next($funs));
            } else {
                return null;
            }
        };
    }

    public function pushHandler(\Closure $fun) {
        $this->handlers[] = function($payload, $next) use ($fun) {
            $res = $fun($payload);
            return is_null($res) ? $next($payload) : $res;
        };
        return $this;
    }

    public function push($middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle($payload) {
        $next = $this->next(array_merge($this->middlewares, $this->handlers));
        return $next($payload);
    }

    public function when($callback, \Closure $handler) {
        $this->handlers[] = function($payload, $next) use ($handler, $callback) {
            if ($payload['callback_id'] === $callback) {
                $res = $handler($payload);
                return is_null($res) ? $next($payload) : $res;
            } else {
                return $next($payload);
            }
        };
        return $this;
    }

    public function hookAfterResponse($payload) {
        $res = $this->handle($payload);
        if (!is_null($res)) {
            $this->client->send($res);
        } else {
            return null;
        }
    }

    public function send(SlackPayload $payload) {
        $this->client->send($payload);
    }
}
