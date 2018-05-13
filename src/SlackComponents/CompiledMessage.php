<?php

namespace SlackComponents;


class CompiledMessage {

    private $message;
    private $channel;
    private $id = null;

    private function __construct($id, $channel, $message) {
        $this->message = $message;
        $this->channel = $channel;
        $this->id = $id;
    }

    private function updatePayload() {
        if (isset($this->message['attachments'])) {
            $this->message['attachments'] = array_map(function($attach) {
                if (isset($attach['actions']) && !isset($attach['callback_id'])) {
                    $data = isset($attach['callback_data']) ? $attach['callback_data'] : [];
                    $attach['callback_id'] = CallbackId::write($this->channel, $data);
                    $this->id = $attach['callback_id'];
                }
                return $attach;
            }, $this->message['attachments']);
        }
    }

    public static function compile($channel, $message) {
        if (is_null($message)) {
            return null;
        }
        $m = new CompiledMessage(null, $channel, $message);
        $m->updatePayload();
        return $m;
    }

    public static function just($message) {
        return new CompiledMessage(null, null, $message);
    }

    public function getMessage() {
        return $this->message;
    }

    public function getChannel() {
        return $this->channel;
    }

    public function getId() {
        return $this->id;
    }

}