<?php

namespace SlackComponents;

class CompiledResource {

    private $resource;
    private $transport;
    private $id = null;

    private function __construct($id, $transport, $resource) {
        $this->resource = $resource;
        $this->transport = $transport;
        $this->id = $id;
    }

    private static function computeCallbackIds($channel, $message) {
        $id = null;
        if (isset($message['attachments'])) {
            $message['attachments'] = array_map(function($attach) use ($channel) {
                if (isset($attach['actions']) && !isset($attach['callback_id'])) {
                    $data = isset($attach['callback_data']) ? $attach['callback_data'] : [];
                    $attach['callback_id'] = CallbackId::write($channel, $data);
                    $id = $attach['callback_id'];
                }
                return $attach;
            }, $message['attachments']);
        }
        return [$id, $message];
    }

    public static function compileMessage($channel, $message) {
        if (is_null($message)) {
            return null;
        }
        list($id, $message) = self::computeCallbackIds($channel, $message);
        return new CompiledResource($id, [
            'type' => ResourceTransport::WEBHOOK,
            'value' => $channel
        ], $message);
    }

    public static function compileResponse($channel, $responseUrl, $message) {
        if (is_null($message)) {
            return null;
        }
        list($id, $message) = self::computeCallbackIds($channel, $message);
        return new CompiledResource($id, [
            'type' => ResourceTransport::RESPONSE_URL,
            'value' => $responseUrl
        ], $message);
    }

    public static function compileDialog($channel, $trigger, $dialog) {
        if (is_null($dialog)) {
            return null;
        }
        $id = null;
        if (isset($dialog['callback_id'])) {
            $id = $dialog['callback_id'];
        } else {
            $data = isset($dialog['callback_data']) ? $dialog['callback_data'] : [];
            $id = CallbackId::write($channel, $data);
            $dialog['callback_id'] = $id;
        }
        return new CompiledResource($id, [
            'type' => ResourceTransport::TRIGGER,
            'value' => $trigger
        ], $dialog);
    }

    public static function just($message) {
        return new CompiledResource(null, null, $message);
    }

    public function getResource() {
        return $this->resource;
    }

    public function getTransport() {
        return $this->transport;
    }

    public function getId() {
        return $this->id;
    }

}