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
    $amplitude = $this->amplitude->reveal();
    return new TelemetryClient($amplitude, $this->telemetryIsEnabled, $this->amplitudeApiKey, $this->amplitudeUserId);
  }

  /**
   * @dataProvider providerTelemetryClient
   */
  public function testTelemetryClient($times_called, $is_enabled, $api_key, $user_id) {
    $this->telemetryIsEnabled = $is_enabled;
    $this->amplitudeApiKey = $api_key;
    $this->amplitudeUserId = $user_id;
    $this->amplitude
      ->init($api_key, $user_id)
      ->shouldBeCalledTimes($times_called);
    $event_type = 'Event type';
    $event_properties = ['key' => 'value'];
    $this->amplitude
      ->logEvent($event_type, $event_properties)
      ->shouldBecalledTimes($times_called);

    $client = $this->createTelemetryClient();
    $client->logEvent($event_type, $event_properties);
    $is_ready = $client->isReady();

    $this->assertEquals($is_enabled, $is_ready, 'Correctly set enabled/state state.');
  }

  public function providerTelemetryClient() {
    return [
      [1, TRUE, 'apikey1', 'drupal/example'],
      [0, FALSE, 'apikey', 'drupal/example'],
      [0, FALSE, NULL, 'drupal/example'],
      [0, FALSE, 'apikey', NULL],
    ];
  }

}
