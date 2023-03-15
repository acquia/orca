<?php

namespace Acquia\Orca\Helper\Log;

/**
 * The client for Domo.
 */
class DomoClient {

  /**
   * The Google API client.
   *
   * @var \Acquia\Orca\Helper\Log\GoogleApiClient
   */
  private $googleApiClient;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Log\GoogleApiClient $googleApiClient
   *   The Google API client.
   */
  public function __construct(GoogleApiClient $googleApiClient) {
    $this->googleApiClient = $googleApiClient;
  }

  /**
   * Sends data to Google api client.
   *
   * @param array $data
   *   The data to be posted to Google.
   *
   * @throws \Acquia\Orca\Exception\OrcaHttpException
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function sendData(array $data): void {
    $this->googleApiClient->postData($data);
  }

}
