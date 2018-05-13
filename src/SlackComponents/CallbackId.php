<?php

namespace SlackComponents;


class CallbackId {

    public static function read($id) {
        try {
            return json_decode(base64_decode($id), true);
        } catch (\Exception $e) {
            return ['channel' => null, 'data' => []];
        }
    }

    public static function write($channel, $data) {
        return base64_encode(json_encode(['channel' => $channel, 'data' => $data]));
    }

}