<?php

namespace Acquia\Orca\Tests\Log;

use Acquia\Orca\Enum\TelemetryEventName;
use Acquia\Orca\Log\TelemetryEventPropertiesBuilder;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Env $env
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask $phpLocTask
 */
class TelemetryEventPropertiesBuilderTest extends TestCase {

  protected function setUp() {
    $this->env = $this->prophesize(\Env::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpLocTask = $this->prophesize(PhpLocTask::class);
  }

  protected function createTelemetryEventPropertiesBuilder(): TelemetryEventPropertiesBuilder {
    /** @var \Env $env */
    $env = $this->env->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask $php_loc_task */
    $php_loc_task = $this->phpLocTask->reveal();
    return new TelemetryEventPropertiesBuilder($env, $filesystem, $php_loc_task);
  }

  public function testConstruction() {
    $builder = $this->createTelemetryEventPropertiesBuilder();

    $this->assertEquals(TelemetryEventPropertiesBuilder::class, get_class($builder), 'Instantiated class.');
  }

  public function testBuildingTestEvent() {
    $builder = $this->createTelemetryEventPropertiesBuilder();

    $event = $builder->build(new TelemetryEventName(TelemetryEventName::TEST));

    $this->assertEquals(['example' => TRUE], $event, 'Built TEST event.');
  }

}
