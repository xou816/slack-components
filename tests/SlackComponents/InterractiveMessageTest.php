<?php

use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client;
use SlackComponents\Components\Button;
use SlackComponents\Interaction\ButtonAction;
use SlackComponents\Components\Style;
use SlackComponents\Components\InterractiveMessage;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Utils\TestUtils;
use SlackComponents\Utils\ApiClient;

class MyMessage extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->button = new Button('btn');
        $this->when($this->button->clicked(function($count) {
            return $this->patchState(['count' => $count + 1]);
        }), 'some_channel');
    }

    protected function buildMessage($count) {
        return [
            'text' => $count,
            'attachments' => [
                [
                    'callback_data' => [
                        'count' => $count
                    ],
                    'actions' => [
                        $this->button
                            ->withLabel('Increment')
                    ]
                ]
            ]
        ];
    }
}

class InterractiveMessageTest extends TestCase {

	private function createSimpleRouter() {
		return Test::createSimpleRouter($this->createMock(Client::class), $this->createMock(ApiClient::class));
	}

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
		$compiled = $msg->build('some_channel', ['count' => 0]);
		$this->assertEquals(0, $compiled->getResource()['text']);
		$payload = TestUtils::getPayload($compiled->getResource(), 'btn', 'btn');
		$resp = $router->handleNow($payload, false);
		$this->assertEquals(1, $resp->getResource()['text']);
	}
}