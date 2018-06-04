<?php

namespace SlackComponents\Utils;

use SlackComponents\Interaction\SlackInteraction;

class TestUtils {

    const USER = ['id' => 'ABC123', 'name' => 'user'];
    const CHANNEL = ['id' => 'EDF456', 'name' => 'channel'];
    const TEAM = ['id' => 'GHI789', 'domain' => 'domain'];

    public static function defaultPayload() {
        return [
            'user' => TestUtils::USER,
            'channel' => TestUtils::CHANNEL,
            'team' => TestUtils::TEAM,
            'action_ts' => strval(microtime(true) + 10),
            'response_url' => '',
            'actions' => [],
            'callback_id' => '',
            'token' => 'default_token',
            'trigger_id' => 'trigger_id'
        ];
    }

    public static function interractiveMessagePayload($message) {
        return array_replace(self::defaultPayload(), [
            'type' => SlackInteraction::MESSAGE,
            'original_message' => $message,
            'message_ts' => strval(microtime(true))
        ]);
    }

    public static function getDialogSubmission($dialog, $values = []) {
        return array_replace(self::defaultPayload(), [
            'type' => SlackInteraction::DIALOG,
            'submission' => $values,
            'callback_id' => $dialog['callback_id']
        ]);
    }

    public static function getPayload($message, $name = null, $value = null) {

        $res = self::interractiveMessagePayload($message);

        foreach ($message['attachments'] as $i => $attach) {

            if (array_key_exists('actions', $attach)) {

                foreach ($attach['actions'] as $action) {

                    if (array_key_exists('value', $action) && $action['value'] == $value && $action['name'] == $name) {
                        $res['actions'] = [$action];
                        $res['callback_id'] = $attach['callback_id'];
                        $res['attachment_id'] = strval($i);
                    } else if (array_key_exists('options', $action)) {
                        $action['selected_options'] = array_values(array_filter($action['options'], function($v) use ($value, $name) {
                            return $v['value'] == $value && $v['name'] = $name;
                        }));
                        unset($action['options']);
                        if (count($action['selected_options'])) {
                            $res['actions'] = [$action];
                            $res['callback_id'] = $attach['callback_id'];
                            $res['attachment_id'] = strval($i);
                        }
                    }
                }
            }
        }

        return $res;
    }
}