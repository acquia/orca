<?php

namespace Acquia\Orca\Event;

use Acquia\Orca\Helper\Log\DomoClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for telemetry events.
 */
class TelemetrySubscriber implements EventSubscriberInterface {

  /**
   * The client for handling Domo.
   *
   * @var \Acquia\Orca\Helper\Log\DomoClient
   */
  private $domoClient;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Log\DomoClient $domoClient
   *   The client for handling Domo.
   */
  public function __construct(DomoClient $domoClient) {
    $this->domoClient = $domoClient;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CiEvent::NAME => 'onCiEvent',
    ];
  }

  /**
   * Sends data to Domo client.
   *
   * @param \Acquia\Orca\Event\CiEvent $event
   *   The Ci event.
   *
   * @throws \Acquia\Orca\Exception\OrcaHttpException
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function onCiEvent(CiEvent $event): void {
    $this->domoClient->sendData($event->getData());
  }

}
