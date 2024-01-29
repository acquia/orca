<?php

namespace Acquia\Orca\Tests\Helper\Log;

use Acquia\Orca\Enum\TelemetryEventNameEnum;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Log\TelemetryEventPropertiesBuilder;
use Acquia\Orca\Tests\TestCase;
use Env\Env;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|Env $env
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 */
class TelemetryEventPropertiesBuilderTest extends TestCase {

  protected ObjectProphecy|Env $env;
  protected ObjectProphecy|Filesystem $filesystem;
  protected OrcaPathHandler|ObjectProphecy $orca;

  protected function setUp(): void {
    $this->env = $this->prophesize(Env::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
  }

  protected function createTelemetryEventPropertiesBuilder(): TelemetryEventPropertiesBuilder {
    $env = $this->env->reveal();
    $filesystem = $this->filesystem->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new TelemetryEventPropertiesBuilder($env, $filesystem, $orca_path_handler);
  }

  public function testConstruction(): void {
    $builder = $this->createTelemetryEventPropertiesBuilder();

    self::assertEquals(TelemetryEventPropertiesBuilder::class, get_class($builder), 'Instantiated class.');
  }

  public function testBuildingTestEvent(): void {
    $builder = $this->createTelemetryEventPropertiesBuilder();

    $event = $builder->build(new TelemetryEventNameEnum(TelemetryEventNameEnum::TEST));

    self::assertEquals(['example' => TRUE], $event, 'Built TEST event.');
  }

}
