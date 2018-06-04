<?php

namespace SlackComponents\Tests;

use GuzzleHttp\Client;
use SlackComponents\Components\Button;
use SlackComponents\Interaction\ButtonAction;
use SlackComponents\Components\Style;
use SlackComponents\Components\CallbackId;
use SlackComponents\Components\InterractiveMessage;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Utils\TestUtils;

class MyMessage extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->button = new Button('btn');
        $this->when($this->button->clicked(function($count) {
            return $this->patchState(['count' => $count + 1]);
        }));
    }

    protected function buildMessage($count) {
        return [
            'text' => $count,
            'attachments' => [
                [
                    'callback_id' => CallbackId::wrap([
                        'count' => 3
                    ]),
                    'actions' => [
                        $this->button
                            ->withLabel('Increment')
                    ]
                ]
            ]
        ];
    }
}

class MessageWithComputation extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->foo = function($count) {
            return 'I have '.strval($count).' foos.';
        };
    }

    protected function buildMessage() {
        return [
            'text' => $this->foo
        ];
    }
}

class InterractiveMessageTest extends SlackTestCase {

    public function testButtonsCanBeCreatedFluently() {
        $btn = new Button('the_name', 'the_value');
        $btn->withStyle(Style::PRIMARY)
            ->withLabel('the_text');
        $this->assertEquals([
            'type' => 'button',
            'style' => 'primary',
            'value' => 'the_value',
            'name' => 'the_name',
            'text' => 'the_text'
        ], $btn->build());
    }

    public function testMessagesRegisterHandlers() {
        $router = $this->createSimpleRouter();
        $msg = new MyMessage($router);
        $compiled = $msg->build('any', ['count' => 0]);
        $this->assertEquals(0, $compiled->getPayload()['text']);
        $payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
        $resp = $router->handle($payload);
        $this->assertEquals(4, $resp->getPayload()['text']);
    }

    public function testAnonymousMessageCanBeCreated() {
        $router = $this->createSimpleRouter();
        $msg = InterractiveMessage::create($router, function($msg) {
            return [
                'text' => $msg
            ];
        });
        $compiled = $msg->build('any', ['msg' => 'Test message']);
        $this->assertEquals('Test message', $compiled->getPayload()['text']);
    }

    public function testMessagesCanDefineComputedProperties() {
        $router = $this->createSimpleRouter();
        $msg = new MessageWithComputation($router);
        $compiled = $msg->build('any', ['count' => 10]);
        $this->assertEquals('I have 10 foos.', $compiled->getPayload()['text']);
        $msg->foo = 'I have some foos.';
        $compiled = $msg->build('any', ['count' => 10]);
        $this->assertEquals('I have some foos.', $compiled->getPayload()['text']);
    }
}