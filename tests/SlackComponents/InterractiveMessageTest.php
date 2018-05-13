<?php

use PHPUnit\Framework\TestCase;

use SlackComponents\Components\Button;
use SlackComponents\Components\Style;

class InterractiveMessageTest extends TestCase {

	public function testButtonsCanBeCreatedFluently() {
		$btn = new Button('the_name', 'the_value');
		$btn->withStyle(Style::PRIMARY)
			->withLabel('the_text');
		$this->assertEquals([
			'type' => 'button',
            'style' => 'primary',
            'value' => 'the_value',
            'name' => 'the_name',
            'text' => 'the_text'
        ], $btn->build());
	}
}