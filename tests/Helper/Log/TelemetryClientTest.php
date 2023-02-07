<?php

namespace Acquia\Orca\Tests\Helper\Log;

use Acquia\Orca\Helper\Log\TelemetryClient;
use Acquia\Orca\Tests\TestCase;

class TelemetryClientTest extends TestCase {

  private $telemetryIsEnabled;

  protected function setUp(): void {
  }

  protected function createTelemetryClient(): TelemetryClient {
    return new TelemetryClient($this->telemetryIsEnabled);
  }

  /**
   * @dataProvider providerTelemetryClient
   */
  public function testTelemetryClient($times_called, $is_enabled): void {
    $this->telemetryIsEnabled = $is_enabled;

    $event_type = 'Event type';
    $event_properties = ['key' => 'value'];

    $client = $this->createTelemetryClient();
    $client->logEvent($event_type, $event_properties);
    $is_ready = $client->isReady();

    self::assertEquals($is_ready, FALSE, 'Correctly set enabled/state state.');
  }

  public function providerTelemetryClient(): array {
    return [
      [1, TRUE],
      [0, FALSE],
      [0, FALSE],
      [0, FALSE],
    ];
  }

}
