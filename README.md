# slack-components (WIP)

A declarative library for Slack interractives messages.

Requirements
------------

PHP 5.5+

Installation
------------

Through [Composer](https://packagist.org/packages/xou816/slack-components): `composer require xou816/slack-components`.


How to use
----------

Example
-------

A basic message:

```php
class MyMessage extends InterractiveMessage {

    protected function buildMessage($state) {
        return ['text' => $state['truc']];
    }

}
```

Adding a button to manage a counter. The state is saved in one of the attachments, through the library-specific `callback_data` key (later translated to a Slack `callback_id`). The method `patchState` calls `buildMessage` but knows how to reuse parts of the submitted message based on state.

```php
class MyMessage extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->myButton = new Button('myButton');
        $this->when($this->myButton->clicked(function(ButtonAction $action, $payload) {
            return $this->patchState('count' => $payload['callback_data']['count'] + 1);
        }), 'someChannel');   
    }

    protected function buildMessage($state) {
        return [
            'text' => $state['count'],
            'attachments' => [
                [
                    'callback_data' => [
                        'count' => $state['count']
                    ],
                    'actions' => [
                        $this->myButton
                            ->withLabel('Increment')
                            ->build()
                    ]
                ]
            ]
        ];
    }
}
```

In order to make lazy components that are not systematically rendered (for instance, a component that needs to fetch data only once), use closures:

```php
class MyMessage extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->myButton = new Button('myButton');
        $this->when($this->myButton->clicked(function(ButtonAction $action, $payload) {
            $reverse = $payload['callback_data']['reverse'];
            $patch = ['count' => $payload['callback_data']['count'] + ($reverse ? -1 : 1)];
            if (abs($patch['count']) === 10) {
                $patch['reverse'] = !$reverse;
            }
            return $this->patchState($patch);
        }), 'someChannel');   
    }

    protected function defaultState() {
        return ['reverse' => false, 'count' => 0];
    }

    protected function buildMessage($state) {
        return [
            'text' => $state['count'],
            'attachments' => [
                [
                    'callback_data' => [
                        'count' => $state['count'],
                        'reverse' => $state['reverse']
                    ],
                    'actions' => function($reverse) {
                        return [
                            $this->myButton
                                ->withLabel($reverse ? 'Decrement' : 'Increment')
                                ->build()
                        ];
                    }
                ]
            ]
        ];
    }
}
```

Here, the button won't be rendered when the user interracts with the message, unless the `reverse` key has been modified with `patchState`. Just add a date in the button label to be convinced!