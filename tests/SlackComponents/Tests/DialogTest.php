<?php

namespace SlackComponents\Tests;

use GuzzleHttp\Client;
use SlackComponents\Components\Dialog;
use SlackComponents\Components\TextInput;
use SlackComponents\Components\Button;
use SlackComponents\Components\CallbackId;
use SlackComponents\Interaction\SlackInteraction;
use SlackComponents\Interaction\DialogSubmission;
use SlackComponents\Components\InterractiveMessage;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\SlackPayload;
use SlackComponents\Utils\TestUtils;

$myDialog = new Dialog([
    new TextInput('name', 'Please enter your name below')
]);

class MyMessageWithDialog extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        global $myDialog;
        parent::__construct($router);
        $this->dialog = $myDialog;
        $this->button = new Button('btn');
        $this->when($this->button->clicked($this->dialog->open()));
        $this->after($this->dialog->submitted(function(DialogSubmission $sub) {
            return $sub->name;
        }));
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

class DialogMessageTest extends SlackTestCase {

	public function testButtonsCanOpenDialog() {
		$router = $this->createSimpleRouter();
		$msg = new MyMessageWithDialog($router);
		$compiled = $msg->build('some_channel', []);
		$payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
        $payload['callback_id'] = CallbackId::just($msg->getCallbackKey());
		$resp = $router->handle($payload);
        $this->assertEquals(SlackPayload::DIALOG, $resp->getType());
	}

    public function testDialogsCanBeSubmitted() {
        global $myDialog;
        $router = $this->createSimpleRouter();
        $msg = new MyMessageWithDialog($router);
        $compiled = $msg->build('some_channel', []);
        $payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
        $payload['callback_id'] = CallbackId::just($msg->getCallbackKey());
        $resp = $router->handle($payload, false);
        $payload = TestUtils::getDialogSubmission($resp->getPayload(), ['name' => 'Roger']);
        $payload['callback_id'] = $payload['callback_id']->build();
        $resp = $router->handle($payload)->getPayload();
        $this->assertEquals('Roger', $resp);
    }
}