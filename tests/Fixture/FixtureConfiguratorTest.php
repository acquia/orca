<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Fixture\Fixture|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class FixtureConfiguratorTest extends TestCase {

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  public function testInstantiation() {
    $this->createFixtureConfigurator();
  }

  private function createFixtureConfigurator(): FixtureConfigurator {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->processRunner->reveal();
    $object = new FixtureConfigurator($fixture, $process_runner);
    $this->assertInstanceOf(FixtureConfigurator::class, $object, 'Instantiated class.');
    return $object;
  }

}
