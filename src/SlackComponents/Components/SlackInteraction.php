<?php

namespace SlackComponents\Components;

interface SlackInteraction {
	const MESSAGE = 'interactive_message';
	const DIALOG = 'dialog_submission';
	public function getRaw();
	public function getType();
}