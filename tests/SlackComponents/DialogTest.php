<?php

use PHPUnit\Framework\TestCase;

use GuzzleHttp\Client;
use SlackComponents\Components\Dialog;
use SlackComponents\Components\TextInput;
use SlackComponents\Components\Button;
use SlackComponents\Interaction\SlackInteraction;
use SlackComponents\Interaction\DialogSubmission;
use SlackComponents\Components\InterractiveMessage;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\ResourceTransport;
use SlackComponents\Routing\CompiledResource;
use SlackComponents\Utils\TestUtils;
use SlackComponents\Utils\ApiClient;

$myDialog = new Dialog([
    new TextInput('name', 'Please enter your name below')
]);

class MyMessageWithDialog extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        global $myDialog;
        parent::__construct($router);
        $this->dialog = $myDialog;
        $this->button = new Button('btn');
        $this->when($this->button->clicked($this->dialog->open()), 'some_channel');
        $this->after($this->dialog->submitted(function(DialogSubmission $sub) {
            return $sub->name;
        }), 'some_channel');
    }

    protected function buildMessage($_) {
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
        $this->assertEquals(ResourceTransport::TRIGGER, $resp->getTransport()['type']);
	}

    public function testDialogsCanBeSubmitted() {
        global $myDialog;
        $router = $this->createSimpleRouter();
        $msg = new MyMessageWithDialog($router);
        $compiled = $msg->build('some_channel', []);
        $payload = TestUtils::getPayload($compiled->getResource(), 'btn', 'btn');
        $resp = $router->handleNow($payload, false);
        $payload = TestUtils::getDialogSubmission($resp->getResource(), ['name' => 'Roger']);
        $resp = $router->handleLater($payload, false)->getResource();
        $this->assertEquals('Roger', $resp);
    }
}