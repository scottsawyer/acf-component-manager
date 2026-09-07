<?php

use PHPUnit\Framework\TestCase;
use AcfComponentManager\Controller\ComponentManager;
use AcfComponentManager\Service\ComponentService;

class TestComponentManager extends TestCase {
	protected $componentManager;

	protected $componentService;

	protected function setUp(): void {
		$this->componentManager = new ComponentManager();
		$this->componentService = new ComponentService();
	}

	public function testGetDiscoveredComponents() {
		$discoveredComponents = $this->componentService->get_discovered_components();
		// Assert $discoveredComponents is an array or contains the expected data.
		$this->assertIsArray($discoveredComponents);
	}

	public function testGetStoredComponents() {
		$storedComponents = $this->componentService->get_stored_components();
		// Assert that $storedComponents is an array or contains the expected data.
		$this->assertIsArray($storedComponents);
	}

	// Add more test methods for other functions in ComponentManager as needed.
}
