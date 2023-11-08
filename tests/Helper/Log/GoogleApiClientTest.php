<?php

namespace Acquia\Orca\Tests\Helper\Log;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Helper\Log\GoogleApiClient;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $version
 */
class GoogleApiClientTest extends TestCase {

  protected DrupalCoreVersionResolver|ObjectProphecy $version;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Contracts\HttpClient\HttpClientInterface
   */
  private HttpClientInterface|ObjectProphecy $httpClient;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Style\SymfonyStyle
   */
  private ObjectProphecy|SymfonyStyle $output;

  /**
   * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
   */
  protected function setUp(): void {

    $this->version = $this->prophesize(DrupalCoreVersionResolver::class);

    $response = $this->prophesize(ResponseInterface::class);
    $response->getStatusCode()
      ->willReturn(200);
    $response->toArray()
      ->willReturn([
        'access_token' => 'sample token',
        'updates' => [
          'updatedData' => [
            'values' => [
              '0' => ['Sample Data', 'Sample Data'],
            ],
          ],
        ],
      ]);
    $this->httpClient = $this->prophesize(HttpClientInterface::class);
    $this->httpClient
      ->request(Argument::cetera())
      ->willReturn($response);
    $this->output = $this->prophesize(SymfonyStyle::class);

  }

  protected function createGoogleClient(): GoogleApiClient {
    $http_client = $this->httpClient->reveal();
    $output_symfony = $this->output->reveal();
    $version_resolver = $this->version->reveal();
    $client_id = "Sample Client";
    $client_secret = "Sample Secret";
    $refresh_token = "Refresh Token";
    return new GoogleApiClient($http_client, $output_symfony, $version_resolver, $client_id, $client_secret, $refresh_token);
  }

  /**
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function testPostDataWithNullVersion(): void {

    $data = [
      'job' => 'STATIC_CODE_ANALYSIS',
      'phase' => 'script',
      'sut' => 'drupal/example',
      'status' => 'PASS',
      'version' => NULL,
      'allowedToFail' => TRUE,
    ];
    $this->version
      ->resolvePredefined(Argument::any())
      ->shouldNotBeCalled();

    $google_client = $this->createGoogleClient();
    $google_client->postData($data);
  }

  /**
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function testPostDataWithLatestDrupalVersion(): void {

    $data = [
      'job' => 'INTEGRATED_TEST_ON_CURRENT',
      'phase' => 'script',
      'sut' => 'drupal/example',
      'status' => 'PASS',
      'version' => DrupalCoreVersionEnum::CURRENT(),
      'allowedToFail' => TRUE,
    ];
    $this->version
      ->existsPredefined(Argument::any())
      ->willReturn(TRUE);
    $this->version
      ->resolvePredefined(Argument::any())
      ->shouldBeCalledOnce();

    $google_client = $this->createGoogleClient();
    self::assertIsString($google_client->getToken());
    $google_client->postData($data);
  }

}
