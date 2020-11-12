<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient;
use Acquia\Orca\Exception\OrcaHttpException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Contracts\HttpClient\HttpClientInterface $httpClient
 */
class DrupalDotOrgApiClientTest extends TestCase {

  private const RESPONSE_XML = '<?xml version="1.0" encoding="utf-8"?>
    <project xmlns:dc="http://purl.org/dc/elements/1.1/">
    <title>Drupal core</title>
    <short_name>drupal</short_name>
    <dc:creator>Drupal</dc:creator>
    <type>project_core</type>
    <supported_branches>8.8.,8.9.,9.0.,9.1.</supported_branches>
    <project_status>published</project_status>
    <link>https://www.drupal.org/project/drupal</link>
    <terms><term><name>Projects</name><value>Drupal core</value></term><term><name>Maintenance status</name><value>Actively maintained</value></term><term><name>Development status</name><value>Under active development</value></term></terms>
    <releases></releases>
    </project>';

  public function setUp() {
    $this->httpClient = $this->prophesize(HttpClientInterface::class);
  }

  private function createDrupalDotOrgApiClient(): DrupalDotOrgApiClient {
    $http_client = $this->httpClient->reveal();
    return new DrupalDotOrgApiClient($http_client);
  }

  public function testGetOldestSupportedDrupalCoreBranch(): void {
    $response = $this->prophesize(ResponseInterface::class);
    $response->getContent()
      ->willReturn(self::RESPONSE_XML)
      ->shouldBeCalledOnce();
    $this->httpClient
      ->request('GET', 'https://updates.drupal.org/release-history/drupal/current')
      ->willReturn($response->reveal())
      ->shouldBeCalledOnce();
    $client = $this->createDrupalDotOrgApiClient();

    $version = $client->getOldestSupportedDrupalCoreBranch();
    // Call again to test value caching.
    $client->getOldestSupportedDrupalCoreBranch();

    self::assertSame('8.8.x', $version);
  }

  /**
   * @dataProvider providerGetOldestSupportedDrupalCoreBranchException
   */
  public function testGetOldestSupportedDrupalCoreBranchException($exception): void {
    $exception = $this->prophesize($exception)->reveal();
    $this->httpClient
      ->request('GET', 'https://updates.drupal.org/release-history/drupal/current')
      ->shouldBeCalledOnce()
      ->willThrow($exception);
    $this->expectException(OrcaHttpException::class);
    $client = $this->createDrupalDotOrgApiClient();

    $client->getOldestSupportedDrupalCoreBranch();
  }

  public function providerGetOldestSupportedDrupalCoreBranchException(): array {
    return [
      [ClientExceptionInterface::class],
      [RedirectionExceptionInterface::class],
      [ServerExceptionInterface::class],
      [TransportExceptionInterface::class],
    ];
  }

}
