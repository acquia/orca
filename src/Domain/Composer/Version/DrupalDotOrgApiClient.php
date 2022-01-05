<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Exception\OrcaHttpException;
use Noodlehaus\Config;
use Noodlehaus\Parser\Xml;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interacts with the Drupal.org APIs.
 *
 * @see https://www.drupal.org/drupalorg/docs/apis
 */
class DrupalDotOrgApiClient {

  /**
   * The HTTP client.
   *
   * @var \Symfony\Contracts\HttpClient\HttpClientInterface
   */
  private $httpClient;

  /**
   * The oldest supported branch of Drupal core.
   *
   * @var string|null
   */
  private $oldestSupportedDrupalCoreBranch;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Contracts\HttpClient\HttpClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(HttpClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Gets the oldest supported branch of Drupal core.
   *
   * @return string
   *   The branch name.
   *
   * @throws \Acquia\Orca\Exception\OrcaHttpException
   *
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getOldestSupportedDrupalCoreBranch(): string {
    if ($this->oldestSupportedDrupalCoreBranch) {
      return $this->oldestSupportedDrupalCoreBranch;
    }

    try {
      $response = $this->httpClient
        ->request('GET', 'https://updates.drupal.org/release-history/drupal/current');
    }
    catch (ExceptionInterface $e) {
      throw new OrcaHttpException('An error occurred accessing the Drupal.org release history API endpoint.', 0, $e);
    }
    $xml = $response->getContent();
    $config = new Config($xml, new Xml(), TRUE);
    $supported_branches = $config->get('supported_branches');
    $parts = explode(',', $supported_branches);
    // Drupal 8 is still marked as supported in the API even though it's EOL.
    // @todo Remove this block once D8 is EOL in the updates API.
    if ($parts[0] === '8.9.') {
      array_shift($parts);
    }
    $this->oldestSupportedDrupalCoreBranch = $parts[0] . 'x';
    return $this->oldestSupportedDrupalCoreBranch;
  }

}
