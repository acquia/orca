<?php

namespace Acquia\Orca\Log;

use Zumba\Amplitude\Amplitude;

/**
 * Provides a telemetry client.
 */
class TelemetryClient {

  /**
   * The Amplitude client.
   *
   * @var \Zumba\Amplitude\Amplitude
   */
  private $amplitude;

  /**
   * Constructs an instance.
   *
   * @param \Zumba\Amplitude\Amplitude $amplitude
   *   The Amplitude client.
   * @param bool|null $telemetry_is_enabled
   *   TRUE if telemetry is enabled or FALSE if not.
   * @param string|null $amplitude_api_key
   *   The Amplitude API key.
   * @param string|null $amplitude_user_id
   *   The Amplitude user ID, typically created by combining the SUT name and
   *   branch, e.g., "drupal/example:8.x-1.x".
   */
  public function __construct(Amplitude $amplitude, ?bool $telemetry_is_enabled = FALSE, ?string $amplitude_api_key = NULL, ?string $amplitude_user_id = NULL) {
    if (!$telemetry_is_enabled || !$amplitude_api_key || !$amplitude_user_id) {
      return;
    }
    $amplitude->init($amplitude_api_key, $amplitude_user_id);
    $this->amplitude = $amplitude;
  }

  /**
   * Logs an event.
   *
   * @param string $type
   *   The event name, e.g., "Fixture created".
   * @param array $properties
   *   An associative array of key/value pairs corresponding to properties or
   *   attributes of the event.
   *
   * @see https://help.amplitude.com/hc/en-us/articles/115000465251#how-should-i-name-my-events
   * @see https://help.amplitude.com/hc/en-us/articles/115000465251#event-properties
   */
  public function logEvent(string $type, array $properties = []): void {
    if (!$this->amplitude) {
      return;
    }
    $this->amplitude->logEvent($type, $properties);
  }

}
