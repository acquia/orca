<?php

namespace Acquia\Orca\Helper\Log;

/**
 * Provides a telemetry client.
 */
class TelemetryClient {

  /**
   * Constructs an instance.
   *
   * @param bool $telemetry_is_enabled
   *   TRUE if telemetry is enabled or FALSE if not.
   */
  public function __construct(bool $telemetry_is_enabled) {

  }

  /**
   * Determines whether or not telemetry is ready.
   *
   * @return bool
   *   TRUE if telemetry is ready or FALSE if not.
   */
  public function isReady(): bool {
    return FALSE;
  }

  /**
   * Logs an event.
   *
   * @param string $type
   *   The event name, e.g., "Fixture created".
   * @param array $properties
   *   An associative array of key/value pairs corresponding to properties or
   *   attributes of the event.
   */
  public function logEvent(string $type, array $properties = []): void {
  }

}
