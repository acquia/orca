<?php

namespace Acquia\Orca\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is called when an ORCA job completes.
 */
class CiEvent extends Event {

  /**
   * Name of the event.
   */
  public const NAME = 'ci.event';

  /**
   * Event data.
   *
   * @var array
   */
  private $data;

  /**
   * Constructs an instance.
   *
   * @param array $data
   *   The event data.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Gets data related to the event.
   *
   * @return array
   *   The array containing event data.
   */
  public function getData(): array {
    return $this->data;
  }

}
