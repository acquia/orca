<?php

namespace Acquia\Orca\Helper\Log;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Exception\OrcaHttpException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interacts with the Google.com APIs.
 *
 * @see https://www.drupal.org/drupalorg/docs/apis
 */
class GoogleApiClient {

  /**
   * The HTTP client.
   *
   * @var \Symfony\Contracts\HttpClient\HttpClientInterface
   */
  private $httpClient;

  /**
   * The Symfony style output.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The Drupal core version resolver.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $version;

  /**
   * The Google client id.
   *
   * @var string
   */
  private $googleApiClientId;

  /**
   * The Google client secret.
   *
   * @var string
   */
  private $googleApiClientSecret;

  /**
   * The Google refresh token.
   *
   * @var string
   */
  private $googleApiRefreshToken;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Contracts\HttpClient\HttpClientInterface $http_client
   *   The http client.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output object.
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $coreVersionResolver
   *   The version resolver.
   * @param string $google_api_client_id
   *   The Google client id.
   * @param string $google_api_client_secret
   *   The Google client secret.
   * @param string $google_refresh_token
   *   The Google refresh token.
   */
  public function __construct(HttpClientInterface $http_client,
  SymfonyStyle $output,
    DrupalCoreVersionResolver $coreVersionResolver,
  $google_api_client_id,
  $google_api_client_secret,
    $google_refresh_token) {
    $this->httpClient = $http_client;
    $this->output = $output;
    $this->version = $coreVersionResolver;
    $this->googleApiClientId = $google_api_client_id;
    $this->googleApiClientSecret = $google_api_client_secret;
    $this->googleApiRefreshToken = $google_refresh_token;
  }

  /**
   * Gets the oldest supported branch of Drupal core.
   *
   * @throws \Acquia\Orca\Exception\OrcaHttpException
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function postData(array $data): void {

    if (!$this->version->existsPredefined($data['version'])) {
      $this->output->comment("No data to Google sheet as test is skipped.");
      return;
    }

    $this->output->section("Sending data to Google sheet");
    $data['version'] = $this->version->resolvePredefined($data['version']);

    $spread_sheet_id = "1CllNKp9W1x0t_B3kKJhsJa5lMAevpxTSgIid4aOz2cE";
    $sheet_id = "Sheet1";
    $access_token = $this->getToken();
    $options = [
      'auth_bearer' => $access_token,
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'query' => [
        'includeValuesInResponse' => 'true',
        'insertDataOption' => 'INSERT_ROWS',
        'valueInputOption' => 'RAW',
      ],
      'json' => [
        "values" => [
          [
            date('m/d/Y'),
            $data['sut'],
            $data['job'],
            $data['version'],
            PHP_VERSION,
            $data['status'],
          ],
        ],
      ],
    ];

    try {
      $response = $this->httpClient
        ->request(
          'POST',
          'https://sheets.googleapis.com/v4/spreadsheets/' . $spread_sheet_id . '/values/' . $sheet_id . ':append',
          $options
        );

      if ($response->getStatusCode() === 200) {
        $this->output->comment("Data successfully posted in google sheet : " .
          implode(',', $response->toArray()['updates']['updatedData']['values'][0]));
      }
    }
    catch (ExceptionInterface $e) {
      throw new OrcaHttpException('An error occurred accessing the Google Sheet API endpoint.', 0, $e);
    }
  }

  /**
   * Gets the access token.
   *
   * @throws \Acquia\Orca\Exception\OrcaHttpException
   */
  private function getToken(): string {

    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'json' => [
        "client_id" => $this->googleApiClientId,
        "client_secret" => $this->googleApiClientSecret,
        "refresh_token" => $this->googleApiRefreshToken,
        "grant_type" => "refresh_token",
      ],
    ];

    try {
      $response = $this->httpClient
        ->request(
          'POST',
          'https://www.googleapis.com/oauth2/v4/token',
          $options
        );

      if ($response->getStatusCode() === 200) {
        $this->output->comment("Access token successfully obtained");
      }

      return $response->toArray()['access_token'];
    }
    catch (ExceptionInterface $e) {
      throw new OrcaHttpException('An error occurred accessing the auth token from Google API endpoint.', 0, $e);
    }
  }

}
