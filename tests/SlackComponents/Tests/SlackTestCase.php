<?php

namespace SlackComponents\Tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client; 
use SlackComponents\Components\CallbackId;
use SlackComponents\Routing\Middleware;
use SlackComponents\Utils\ApiClient;
use SlackComponents\Utils\TestUtils;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\SlackPayload;

class SlackTestCase extends TestCase {

    public function createBlankRouter() {
        $mock = $this->createMock(Client::class);
        return new SlackRouter($mock);
    }

    public function createSimpleRouter($safe = false) {
        $mock = $this->createMock(Client::class);
        $router = new SlackRouter($mock);
        if ($safe) $router->push($router->checkToken());
        return $router->push(Middleware::parseCallbacks())
            ->push(Middleware::parseUser())
            ->push(Middleware::parseInteractions());
    }

    public function createSimpleMessage($text = 'Hello world') {
        return [
            'text' => $text,
            'attachments' => [
                [
                    'actions' => [
                        [
                            'name' => 'btn',
                            'text' => 'Button',
                            'type' => 'button',
                            'value' => 'pressed'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function respondWith($message) {
        return function($payload) use ($message) {
            return is_null($message) ? null : 
            SlackPayload::create(SlackPayload::RESPONSE, null, $message);
        };
    }

    public function compile($message) {
        return SlackPayload::create(SlackPayload::WEBHOOK, null, $message);
    }

    public function trigger($key) {
        $payload = TestUtils::defaultPayload([]);
        $payload['callback_id'] = $key;
        return $payload;
    }

}