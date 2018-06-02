<?php

namespace SlackComponents\Tests;

use GuzzleHttp\Client;
use SlackComponents\Components\Dialog;
use SlackComponents\Components\TextInput;
use SlackComponents\Components\Button;
use SlackComponents\Components\Select;
use SlackComponents\Components\CallbackId;
use SlackComponents\Interaction\SlackInteraction;
use SlackComponents\Interaction\DialogSubmission;
use SlackComponents\Components\InterractiveMessage;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\SlackPayload;
use SlackComponents\Utils\TestUtils;

$myDialog = Dialog::create('Test dialog')
    ->withElements([
        TextInput::create('name')
            ->withLabel('Please enter your name below'),
        Select::create('select')
            ->withOption('opt1', 'Option 1')
    ]);

class MyMessageWithDialog extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        global $myDialog;
        parent::__construct($router);
        $this->dialog = $myDialog;
        $this->button = new Button('btn');
        $this->when($this->button->clicked($this->dialog->open()));
        $this->when($this->dialog->submitted(function(DialogSubmission $sub) {
            return $sub->name;
        }));
    }

    protected function buildMessage($_) {
        return [
            'text' => 'Dialog demo',
            'attachments' => [
                [
                    'callback_id' => CallbackId::wrap(),
                    'actions' => [
                        $this->button
                            ->withLabel('Open dialog')
                    ]
                ]
            ]
        ];
    }
}

class DialogMessageTest extends SlackTestCase {

	public function testButtonsCanOpenDialog() {
		$router = $this->createSimpleRouter();
		$msg = new MyMessageWithDialog($router);
		$compiled = $msg->build('some_channel', []);
		$payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
		$resp = $router->handle($payload);
        $this->assertEquals(SlackPayload::DIALOG, $resp->getType());
	}

    public function testDialogsCanBeSubmitted() {
        global $myDialog;
        $router = $this->createSimpleRouter();
        $msg = new MyMessageWithDialog($router);
        $compiled = $msg->build('some_channel', []);
        $payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
        $resp = $router->handle($payload);
        $payload = TestUtils::getDialogSubmission($resp->getPayload(), ['name' => 'Roger']);
        $resp = $router->handle($payload)->getPayload();
        $this->assertEquals('Roger', $resp);
    }
}