<?php

namespace Acquia\Orca\Tests\Command\Internal;

use Acquia\Orca\Command\Internal\InternalLogEventCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Enum\TelemetryEventName;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Log\TelemetryEventPropertiesBuilder;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Log\TelemetryClient $telemetryClient
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Log\TelemetryEventPropertiesBuilder $telemetryEventBuilder
 */
class InternalLogEventCommandTest extends CommandTestBase {

  public function setUp() {
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
    /** @var \Acquia\Orca\Log\TelemetryClient $telemetry_client */
    $telemetry_client = $this->telemetryClient->reveal();
    /** @var \Acquia\Orca\Log\TelemetryEventPropertiesBuilder $telemetry_event_builder */
    $telemetry_event_builder = $this->telemetryEventBuilder->reveal();
    return new InternalLogEventCommand($telemetry_client, $telemetry_event_builder);
  }

  public function testHappyPath() {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1);
    $event = TelemetryEventName::TEST();
    $properties = ['key' => 'value'];
    $this->telemetryEventBuilder
      ->build($event)
      ->shouldBeCalledTimes(1)
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent($event->getValue(), $properties)
      ->shouldBeCalledTimes(1);

    $this->executeCommand(['name' => $event->getKey()]);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testWithInvalidNameArgument() {
    $name = 'invalid';
    $this->executeCommand(['name' => 'invalid']);

    $this->assertEquals(sprintf('Error: Invalid value for "name" argument: "%s".', $name) . PHP_EOL
      . 'Hint: Acceptable values are "TRAVIS_CI_JOB", "TEST".' . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testWithoutArguments() {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageRegExp('/^Not enough arguments/');

    $this->executeCommand();
  }

  public function testWithTelemetryDisabled() {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1)
      ->willReturn(FALSE);

    $this->executeCommand(['name' => TelemetryEventName::TEST]);

    $this->assertEquals('Notice: Nothing logged. Telemetry is disabled.' . PHP_EOL .
      'Hint: https://github.com/acquia/orca/blob/master/docs/advanced-usage.md#ORCA_TELEMETRY_ENABLE' . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

}
