# slack-components

A declarative library for Slack interractives messages.

Features
========
- powerful : manage smart, stateful components
- simple and flexible : choose not to use components at will
- easy to test : mock Slack interactions against your messages
- compatible with PHP 5.5+, only depends on Guzzle


Installation
============

Through [Composer](https://packagist.org/packages/xou816/slack-components): `composer require xou816/slack-components`.

Getting started
===============

This guide assumes you are somewhat familiar with [Slack interactive messages](https://api.slack.com/interactive-messages).

Creating a router
-----------------

The `SlackRouter` is responsible for handling incoming Slack interactions as well as dispatching your messages, dialogs, etc.

```php
// $client is a GuzzleHttp\Client
$router = new SlackRouter($client, $options); 
```

Valid options:

| Options   | Description |
| --------- | ----------- |
| webhooks  | An array mapping channel names to corresponding webhooks |
| app_token | A token (`xoxp...`) that grants your application permissions |
| token     | A token that is checked against the one coming from Slack when interacting |

Alternatively, you may build a router with the default middlewares enabled:

```php
// $client is still a GuzzleHttp\Client
$router = SlackRouter::defaults($client, $options); 
```

Request URL
-----------

Once you set up a Request URL and a matching route in your app, respond `HTTP 200` to requests coming in from Slack, and call `hookAfterResponse` once your response has been sent.

For instance, after the `terminate` event in Symfony, add the following snippet, where `$router` is a `SlackRouter` instance and `$request` a Symfony request:

```php
$payload = json_decode($request->request->get('payload'), true);
$router->hookAfterResponse($payload);
``` 

This hook is responsible for sending responses to Slack interactions.

Middlewares
-----------

Middlewares are functions which can alter the incoming Slack action payload before it is handled:

```php
$router->push(function($payload, $next) {
    $modified = modify($payload);
    return $next($modified);
});
``` 

Or modify the response that is sent:

```php
$router->push(function($payload, $next) {
    $response = $next($payload);
    return modify($response);    
});
``` 

Default middlewares:
- `$router->checkToken()`: validates the payload token, you must have an instance of a router to use that middleware, and it should be the first one in the stack
- `Middleware::parseCallbacks()`: replaces raw callback IDs with `CallbackId` components
- `Middleware::parseInteractions()`: replaces interactions with their corresponding `SlackInteraction` object
- `Middleware::parseUser()`: replaces the payload user with a `SlackUser` object
- `Middleware::wrapResponse()`: wraps plain object in proper `SlackResponse` objects (uses the `response_url` by default).

Other middlewares could be written to handle authorization, logging, etc.

Send a message
--------------

Sending a sample (static) message:

```php
$msg = new SlackPayload(SlackPayload::WEBHOOK, '#channel', 'Hello world!'); // uses the webhooks option
$router->send($msg);
```

Fortunately, interactive messages save you the trouble of having to build the `SlackPayload` yourself, thanks to smart components.

Interactions
------------

Use the `when` method to register a handler that is triggered by a specific callback ID. A callback ID is often attached to actions in messages you send.

```php
$router->when('callback', function($payload) {
    // if $payload matches a button press...
});
```


Components
==========

Components are used to simplify the process of building a Slack message. Say you intend to post a message, with a click counter. Instead of sending this (which you can do!):

```php
[
    'text' => $count,
    'attachments' => [
        [
            'callback_id' => 'mycallback',
            'actions' => [
                [
                    'type' => 'button',
                    'style' => 'primary',
                    'value' => 'increment',
                    'name' => 'increment',
                    'text' => 'Increment'
                ]
            ]
        ]
    ]
]
```

You might end up writing this:

```php
[
    'text' => $count,
    'attachments' => [
        [
            'callback_id' => CallbackId::wrap(['count' => 0]),
            'actions' => [
                Button::create('btn_name')->withLabel('Increment')
            ]
        ]
    ]
]
```

How is that better? Well, apart from code completion concerns in your IDE, using components will greatly ease the process of responding to actions and managing **state**. Indeed, in this example, you need to keep track of the clicks.

Managing state
--------------

This library offers state management through the callback ID. As it is being sent along every message and interaction, it seems to be a fitting candidate to solve our problem of state management.

It can either be used to store the "full" state, when small enough...

```php
['count' => 0]
```

...or it can help to keep track of a larger representation of that state in a database.

```php
['state_id' => 123]
```

The `CallbackId` component is a fairly simple component that results in a string when *built* -- a base 64 encoding of a key and some data:

```php
$callbackId
    ->withKey('mykey')
    ->withData(['count' => 0])
``` 

The **key** identifies where the message comes from (and what will be able to handle future interactions), while the **data** can be used to store state.

You **must** use the `parseCallbacks` middleware if you want to work with incoming callback IDs in such a way.

Building components
-------------------

Components are complex objects, and we need to send plain objects (JSON) to Slack. Therefore, components have to go through a **build step**: first, the full component tree is built, and then it is rendered to a plain object.

Components might feel similar to what can be found in popular frontend frameworks, but there a few important differences, one of which being that *the state in a tree of components is shared by all components*.

The root component can usually be accessed through the `getContext` method of components.

Why state matters
-----------------

Given a previous render of a component tree (such as the `original_message` sometimes provided by Slack) and a previous state (saved in our callback ID), we are able to efficiently rebuild the tree (read: our message)... but more on that later.

Interactions
------------

Components offer a convenient way of registering interaction handlers in the router. For instance, with the `parseInteractions` middleware enabled:

```php
$router->when('callback', $button->clicked(function(ButtonAction $action) {
    // ...
}));
```

This handler will fire for the specified callback, when the button represented by the `$button` instance has been clicked.

Reflection
----------

Reflection is used in handlers just as the one supplied to `clicked`. You may therefore inject the following elements in such closures:
- the interaction object, by requesting a subclass of `SlackInteraction`, for instance, `ButtonAction`, `DialogSubmission`
- the user responsible for the interaction, by requesting a `SlackUser`-typed argument
- the full Slack payload, by requesting a single argument, or an argument named exactly `payload`
- a state key, by requesting an argument with the exact same name.

Instead of a closure, you may instead supply a callable.

Responding to interactions
--------------------------

When responding to an interaction, you may return one of the following:
- a message (built): for instance, using `InteractiveMessage::patchState`, but it can also be a completely new message
- a request to open a dialog (see dialogs), using `Dialog::open` or `Dialog::doOpen`
- a message to the user who triggered the action with `SlackUser::sendMessage`.

Interactive messages
====================

Interactive messages offer a convenient way of managing components, interactions and callback keys and state in a single place.

```php
class MyMessage extends InteractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->increment = new Button('increment');
        $this->when($this->increment->clicked(function($count) {
            return $this->patchState(['count' => $count + 1]);
        }));
    }

    protected function buildMessage($count) {
        return [
            'text' => $count,
            'attachments' => [
                [
                    'callback_id' => $this->callback([
                        'count' => $count
                    ]),
                    'actions' => [
                        $this->increment
                            ->withLabel('Increment')
                    ]
                ]
            ]
        ];
    }
}
```

Things to note:
- we use a class to represent our message
- its content is described by `buildMessage`
- we listen for interactions on our `increment` button using `when`
- we store the state of the message using `callback`
- when interacting, we patch our message, that is, we render it again with an updated state.

You may also build a so-called anonymous message using `InteractiveMessage::create($router, $buildMesssageClosure)`. 

A callback key is automatically chosen for you, based on the message class. That's why you do not have to supply one to the message's `when` method.

Building and sending
--------------------

Assuming you have an instance of your message class:

```php
$built = $myMessage->build('#channel', ['count' => 0]);
$myMessage->send($built);
// or...
$myMessage->buildAndSend('#channel', ['count' => 0]);
```

The message will be sent to the specified channel using your webhooks.

The `send` method takes any `SlackPayload` object.

Patching messages
-----------------

Patching messages means updating the state with a patch (a subset of the new state). The `patchState` method returns a built message (a plain object).

The `patchState` method is particularly helpful when using a `LazyComponent`. The latter is only rendered when needed -- if we have a previous render of it, and the relevant part of the state it *depends on* have not changed, we just keep the previous rendering.

This is very useful if you need to perform an expensive computation for a particular component -- wrap it in a `LazyComponent`!

Such components can be built using a simple closure, which arguments indicate the state we depend on:

```php
function($count) {
    return /**/;
}
```

This is a valid component, which renders only when the `count` changes in the state. Here is a complete example:

```php
class MyMessage extends InteractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->increment = new Button('increment');
        $this->when($this->increment->clicked(function($count, $reverse) {
            $patch = ['count' => $count + ($reverse ? -1 : 1)];
            if (abs($patch['count']) === 10) {
                $patch['reverse'] = !$reverse;
            }
            return $this->patchState($patch);
        }));   
    }

    protected function defaultState() {
        return ['reverse' => false, 'count' => 0];
    }

    protected function buildMessage($count, $reverse) {
        return [
            'text' => $count,
            'attachments' => [
                [
                    'callback_id' => $this->callback([
                        'count' => $count,
                        'reverse' => $reverse
                    ]),
                    'actions' => function($reverse) {
                        return [
                            $this->increment
                                ->withLabel($reverse ? 'Decrement' : 'Increment')
                        ];
                    }
                ]
            ]
        ];
    }
}
```

In that example, the `actions` part of the message would only be computed once in ten clicks -- until the labels needs to be changed! Not convinced? Display the date in this button!

Computed properties
-------------------

Computed properties are properties that directly depend on the state and which you want to avoid computing all too often.

For instance, if you store a very complex state in your database, you might access it by saving its database ID in the state of your message.

However, if you happen to have fetched this state from your database at some other moment, you want to avoid having to fetch it inside your message.

Creating a computed property:

```php
// assuming $state = ['id' => ..., ...]
$this->fullState = function($id) {
    return $this->database->fetch($id);
};
```

Accessing it:

```php
$fullState = $this->fullState;
```

Assigning an existing computation:

```php
$this->fullState = $theFullState;
```

Troubleshooting
---------------

**Important note.** Instances of your messages must exist for interaction handlers to be registered in the router.

Usual components
================

In a message: `Button`, `Select`. You may attach (reflection-ready) handlers to these components using `clicked` and `selected` respectively.

In a dialog: `TextInput`, `Textarea`, `Select`.

Dialogs
-------

Dialogs are not usual components, as they are not attached directly to the message. However, much like buttons or selects, they provide an easy way to respond to interactions.

```php
$myDialog = Dialog::create('Test dialog')
    ->withElements([
        function($default) {
            return TextInput::create('name')
                ->withValue($default)
                ->withLabel('Please enter your name below');
        },
        Select::create('select')
            ->withOption('opt1', 'Option 1')
            ->withOption('opt2', 'Option 2')
    ]);
```

The dialog has no state on its own, when you first create it, and is not attached to the message body like a button would. However, when an interaction results in a dialog opening, that interaction carries a callback ID, and therefore a state. This state is communicated to dialog, and components are able to query it (that is precisely what the `LazyComponent`/closure does above).

```php
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
                        'default' => 'Robert' // this part is communcated to the dialog
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
```

In order to open a dialog, an interaction must first occur. You may then call one of the following methods:
- `doOpen`, which returns an appropriate closure
- or `open`, which you **must** give the full interaction `$payload`.

Once open, the dialog can be used much like a button or a select, and you may react to submissions using `submitted`. 