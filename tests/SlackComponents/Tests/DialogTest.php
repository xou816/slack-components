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
use SlackComponents\Components\InteractiveMessage;
use SlackComponents\Routing\SlackRouter;
use SlackComponents\Routing\SlackPayload;
use SlackComponents\Utils\TestUtils;

$myDialog = Dialog::create('Test dialog')
    ->withElements([
        function($default) {
            return TextInput::create('name')
                ->withValue($default)
                ->withLabel('Please enter your name below');
        },
        Select::create('select')
            ->withOption('opt1', 'Option 1')
    ]);

class MyMessageWithDialog extends InteractiveMessage {

    private $dialog;
    private $button;

    public function __construct(SlackRouter $router) {
        global $myDialog;
        parent::__construct($router);
        $this->dialog = $myDialog;
        $this->button = new Button('btn');
        $this->when($this->button->clicked($this->dialog->doOpen()));
        $this->when($this->dialog->submitted(function(DialogSubmission $sub, $greet) {
            return $greet.', '.$sub->name;
        }));
    }

    protected function buildMessage($greet) {
        return [
            'text' => 'Dialog demo',
            'attachments' => [
                [
                    'callback_id' => $this->callback([
                        'greet' => $greet,
                        'default' => 'Robert'
                    ]),
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
        $compiled = $msg->build('some_channel', ['greet' => 'Hello']);
        $payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
        $resp = $router->handle($payload);
        $this->assertEquals(SlackPayload::DIALOG, $resp->getType());
        $this->assertEquals('Robert', $resp->getPayload()['elements'][0]['value']);
    }

    public function testDialogsCanBeSubmitted() {
        $router = $this->createSimpleRouter();
        $msg = new MyMessageWithDialog($router);
        $compiled = $msg->build('some_channel', ['greet' => 'Hello']);
        $payload = TestUtils::getPayload($compiled->getPayload(), 'btn', 'btn');
        $resp = $router->handle($payload);
        $payload = TestUtils::getDialogSubmission($resp->getPayload(), ['name' => 'Roger']);
        $resp = $router->handle($payload)->getPayload();
        $this->assertEquals('Hello, Roger', $resp);
    }
}