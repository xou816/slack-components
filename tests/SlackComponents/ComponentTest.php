<?php

use PHPUnit\Framework\TestCase;
use SlackComponents\Components\AbstractComponent;
use SlackComponents\Components\ArrayComponent;
use SlackComponents\Components\LazyComponent;

class TestableComponent extends AbstractComponent {

	private $children;

	public function __construct($children = []) {
		$this->children = $children;
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

class ComponentTest extends TestCase {

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

	public function testComponentCarryState() {
		$comp = new TestableComponent();
		$comp->restoreState([
			'callback_data' => ['stored_key' => true]
		], ['key' => 'custom']);
		$this->assertEquals($comp->getState(), [
			'key' => 'custom',
			'stored_key' => true
		]);
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

}