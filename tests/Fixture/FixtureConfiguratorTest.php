<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class FixtureConfiguratorTest extends TestCase {

  protected function setUp() {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  public function testInstantiation() {
    $this->createFixtureConfigurator();
  }

  private function createFixtureConfigurator(): FixtureConfigurator {
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->processRunner->reveal();
    $object = new FixtureConfigurator($process_runner);
    $this->assertInstanceOf(FixtureConfigurator::class, $object, 'Instantiated class.');
    return $object;
  }

}
