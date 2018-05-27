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

TODO

Example
-------

A basic message:

```php
class MyMessage extends InterractiveMessage {

    protected function buildMessage($text) {
        return ['text' => $text];
    }

}
```

Adding a button to manage a counter. The state is saved in one of the attachments, through the library-specific `callback_data` key (later translated to a Slack `callback_id`). The method `patchState` calls `buildMessage` but knows how to reuse parts of the submitted message based on state.

```php
class MyMessage extends InterractiveMessage {

    public function __construct(SlackRouter $router) {
        parent::__construct($router);
        $this->myButton = new Button('myButton');
        $this->when($this->myButton->clicked(function($count) {
            return $this->patchState(['count' => $count + 1]);
        }), 'someChannel');   
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
                        $this->myButton
                            ->withLabel('Increment')
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
        $this->when($this->myButton->clicked(function($count, $reverse) {
            $patch = ['count' => $count + ($reverse ? -1 : 1)];
            if (abs($patch['count']) === 10) {
                $patch['reverse'] = !$reverse;
            }
            return $this->patchState($patch);
        }), 'someChannel');   
    }

    protected function defaultState() {
        return ['reverse' => false, 'count' => 0];
    }

    protected function buildMessage($count, $reverse) {
        return [
            'text' => $count,
            'attachments' => [
                [
                    'callback_data' => [
                        'count' => $count,
                        'reverse' => $reverse
                    ],
                    'actions' => function($reverse) {
                        return [
                            $this->myButton
                                ->withLabel($reverse ? 'Decrement' : 'Increment')
                        ];
                    }
                ]
            ]
        ];
    }
}
```

Here, the button won't be rendered when the user interracts with the message, unless the `reverse` key has been modified with `patchState`. Just add a date in the button label to be convinced!