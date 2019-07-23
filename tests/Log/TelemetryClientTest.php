<?php

namespace Acquia\Orca\Log;

use PHPUnit\Framework\TestCase;
use Zumba\Amplitude\Amplitude;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Zumba\Amplitude\Amplitude $amplitude
 */
class TelemetryClientTest extends TestCase {

  private $amplitudeApiKey;

  private $amplitudeUserId;

  private $telemetryIsEnabled;

  public function setUp() {
    $this->amplitude = $this->prophesize(Amplitude::class);
  }

  protected function createTelemetryClient(): TelemetryClient {
    /** @var \Zumba\Amplitude\Amplitude $amplitude */
    $amplitude = $this->amplitude->reveal();
    return new TelemetryClient($amplitude, $this->telemetryIsEnabled, $this->amplitudeApiKey, $this->amplitudeUserId);
  }

  /**
   * @dataProvider providerTelemetryClient
   */
  public function testTelemetryClient($times_called, $telemetry_is_enabled, $amplitude_api_key, $amplitude_user_id, $event_type, $event_properties) {
    $this->telemetryIsEnabled = $telemetry_is_enabled;
    $this->amplitudeApiKey = $amplitude_api_key;
    $this->amplitudeUserId = $amplitude_user_id;
    $this->amplitude
      ->init($amplitude_api_key, $amplitude_user_id)
      ->shouldBeCalledTimes($times_called);
    $this->amplitude
      ->logEvent($event_type, $event_properties)
      ->shouldBecalledTimes($times_called);

    $client = $this->createTelemetryClient();
    $client->logEvent($event_type, $event_properties);
  }

  public function providerTelemetryClient() {
    return [
      [1, TRUE, 'apikey1', 'drupal/example1', 'Event type 1', ['key' => 'value']],
      [1, TRUE, 'apikey2', 'drupal/example2', 'Event type 2', []],
      [0, FALSE, 'apikey', 'drupal/example', '', []],
      [0, TRUE, NULL, 'drupal/example', '', []],
      [0, TRUE, 'apikey', NULL, '', []],
    ];
  }

}
