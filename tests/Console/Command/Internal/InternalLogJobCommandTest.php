<?php

namespace Acquia\Orca\Tests\Console\Command\Internal;

use Acquia\Orca\Console\Command\Internal\InternalLogJobCommand;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Enum\TelemetryEventNameEnum;
use Acquia\Orca\Helper\Log\TelemetryClient;
use Acquia\Orca\Helper\Log\TelemetryEventPropertiesBuilder;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Log\TelemetryClient $telemetryClient
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Log\TelemetryEventPropertiesBuilder $telemetryEventBuilder
 */
class InternalLogJobCommandTest extends CommandTestBase {

  protected ObjectProphecy|TelemetryClient $telemetryClient;
  protected ObjectProphecy|TelemetryEventPropertiesBuilder $telemetryEventBuilder;

  protected function setUp(): void {
    $this->telemetryClient = $this->prophesize(TelemetryClient::class);
    $this->telemetryClient
      ->isReady()
      ->willReturn(TRUE);
    $this->telemetryEventBuilder = $this->prophesize(TelemetryEventPropertiesBuilder::class);
    $this->telemetryClient
      ->logEvent(Argument::any())
      ->shouldNotBeCalled();
  }

  protected function createCommand(): Command {
    $telemetry_client = $this->telemetryClient->reveal();
    $telemetry_event_builder = $this->telemetryEventBuilder->reveal();
    return new InternalLogJobCommand($telemetry_client, $telemetry_event_builder);
  }

  public function testHappyPath(): void {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1);
    $event = TelemetryEventNameEnum::TRAVIS_CI_JOB();
    $properties = ['key' => 'value'];
    $this->telemetryEventBuilder
      ->build($event)
      ->shouldBeCalledTimes(1)
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent($event->getValue(), $properties)
      ->shouldBeCalledTimes(1);

    $this->executeCommand();

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testWithTelemetryDisabled(): void {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1)
      ->willReturn(FALSE);

    $this->executeCommand();

    self::assertEquals('Notice: Nothing logged. Telemetry is disabled.' . PHP_EOL .
      'Hint: https://github.com/acquia/orca/blob/main/docs/advanced-usage.md#ORCA_TELEMETRY_ENABLE' . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testSimulateOption(): void {
    $properties = ['test' => 'example'];
    $this->telemetryEventBuilder
      ->build(TelemetryEventNameEnum::TRAVIS_CI_JOB())
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent(Argument::any())
      ->shouldNotBeCalled();

    $this->executeCommand(['--simulate' => TRUE]);

    self::assertEquals(print_r($properties, TRUE), $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testTestOption(): void {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1);
    $event = TelemetryEventNameEnum::TEST();
    $properties = ['key' => 'value'];
    $this->telemetryEventBuilder
      ->build($event)
      ->shouldBeCalledTimes(1)
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent($event->getValue(), $properties)
      ->shouldBeCalledTimes(1);

    $this->executeCommand(['--test' => TRUE]);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

}
