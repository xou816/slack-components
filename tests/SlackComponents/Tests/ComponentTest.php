<?php

namespace SlackComponents\Tests;

use SlackComponents\Components\CallbackId;
use SlackComponents\Components\AbstractComponent;
use SlackComponents\Components\ArrayComponent;
use SlackComponents\Components\LazyComponent;

class TestableComponent extends AbstractComponent {

	private $children;

	public function __construct($children = []) {
		$this->children = $children;
	}

	public function someMethod() {
		return 'someMethod result';
	}

	protected function getContext() {
		return $this;
	}

    protected function isInterestedIn($patch) {
    	return false;
    }

    protected function defaultState() {
    	return ['key' => 'default'];
    }

    protected function buildTree($state) {
    	return $this->children;
    }

    public function patchState($patch = []) {
    	parent::patchState($patch);
    } 

    public function getState() {
    	return parent::getState();
    }

    public function restoreState($rendered, $state) {
    	parent::restoreState($rendered, $state);
    }

}

class ComponentTest extends SlackTestCase {

	public function testComponentsRenderToPlainArrays() {
		$comp = new TestableComponent([
			new ArrayComponent([1, 2]),
			true
		]);
		$comp->patchState();
		$render = $comp->getRendered();
		array_walk_recursive($render, function($leaf) {
			$this->assertTrue(is_string($leaf) || is_bool($leaf) || is_numeric($leaf) || is_null($leaf));
		});
		$this->assertEquals($render, [
			[1, 2],
			true
		]);
	}

	public function testComponentsCarryState() {
		$comp = new TestableComponent();
		$comp->restoreState([
			'callback_id' => CallbackId::wrap(['stored_key' => true])
		], ['key' => 'custom']);
		$this->assertEquals([
			'key' => 'custom',
			'stored_key' => true
		], $comp->getState());
	}

	public function testLazyComponentRendersIfInterested() {
		$comp = new TestableComponent([
			'example' => function($stableKey) {
				return 'wontRender';
			},
			'updated' => true
		]);
		$comp->restoreState(['example' => 'somethingElse', 'updated' => false], []);
		$comp->patchState();
		$this->assertEquals($comp->getRendered(), ['example' => 'somethingElse', 'updated' => true]);
		$comp->patchState(['stableKey' => 'whatever']);
		$this->assertEquals($comp->getRendered(), ['example' => 'wontRender', 'updated' => true]);
	}

	public function testClosuresAreBoundToRootContext() {
		$comp = new TestableComponent([
			'example' => function($key) {
				return $this->someMethod();
			},
		]);
		$comp->patchState(['key' => 'whatever']);
		$this->assertEquals('someMethod result', $comp->getRendered()['example']);
	}

}