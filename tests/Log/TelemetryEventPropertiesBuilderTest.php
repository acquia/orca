<?php

namespace Acquia\Orca\Tests\Log;

use Acquia\Orca\Enum\TelemetryEventName;
use Acquia\Orca\Log\TelemetryEventPropertiesBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Env $env
 */
class TelemetryEventPropertiesBuilderTest extends TestCase {

  protected function setUp() {
    $this->env = $this->prophesize(\Env::class);
  }

  protected function createTelemetryEventPropertiesBuilder(): TelemetryEventPropertiesBuilder {
    /** @var \Env $env */
    $env = $this->env->reveal();
    return new TelemetryEventPropertiesBuilder($env);
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
