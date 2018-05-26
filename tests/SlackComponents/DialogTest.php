<?php

use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client;
use SlackComponents\Components\Dialog;
use SlackComponents\Components\TextInput;
use SlackComponents\Components\Button;
use SlackComponents\InterractiveMessage;
use SlackComponents\SlackRouter;
use SlackComponents\Utils\TestUtils;
use SlackComponents\Utils\ApiClient;

class MyMessageWithDialog extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->dialog = new Dialog([
            new TextInput('name', 'Please enter your name below')
        ]);
        $this->button = new Button('btn');
        $this->when($this->button->clicked($this->dialog->open()), 'some_channel');
    }

    protected function buildMessage($s) {
        return [
            'text' => 'Dialog demo',
            'attachments' => [
                [
                    'actions' => [
                        $this->button
                            ->withLabel('Open dialog')
                    ]
                ]
            ]
        ];
    }
}

class DialogMessageTest extends TestCase {

	private function createSimpleRouter() {
		return Test::createSimpleRouter($this->createMock(Client::class), $this->createMock(ApiClient::class));
	}

	public function testButtonsCanOpenDialog() {
		$router = $this->createSimpleRouter();
		$msg = new MyMessageWithDialog($router);
		$compiled = $msg->build('some_channel', []);
		$payload = TestUtils::getPayload($compiled->getResource(), 'btn', 'btn');
		$resp = $router->handleNow($payload, false);
	}
}