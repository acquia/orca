<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient;
use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Acquia\Orca\Tests\Enum\DrupalCoreVersionEnumsTestTrait;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient|\Prophecy\Prophecy\ObjectProphecy $drupalDotOrgApiClient
 * @property \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory|\Prophecy\Prophecy\ObjectProphecy $selectorFactory
 * @property \Composer\Package\PackageInterface|\Prophecy\Prophecy\ObjectProphecy $package
 * @property \Composer\Package\Version\VersionSelector|\Prophecy\Prophecy\ObjectProphecy $selector
 */
class DrupalCoreVersionResolverTest extends TestCase {

  use DrupalCoreVersionEnumsTestTrait;

  private const CURRENT = '9.1.0';

  protected function setUp(): void {
    $this->drupalDotOrgApiClient = $this->prophesize(DrupalDotOrgApiClient::class);
    $this->drupalDotOrgApiClient
      ->getOldestSupportedDrupalCoreBranch()
      ->willReturn('8.8.x');
    $this->package = $this->prophesize(PackageInterface::class);
    $package = $this->package->reveal();
    $this->selector = $this->prophesize(VersionSelector::class);
    $this->selector
      ->findBestCandidate('drupal/core', Argument::any(), NULL, Argument::any())
      ->willReturn($package);
    $this->selector
      ->findBestCandidate('drupal/core', '*', NULL, 'stable')
      ->willReturn($package);
    $this->selectorFactory = $this->prophesize(VersionSelectorFactory::class);
  }

  private function createDrupalCoreVersionResolver(): DrupalCoreVersionResolver {
    $drupal_dot_org_api_client = $this->drupalDotOrgApiClient->reveal();
    $selector = $this->selector->reveal();
    $this->selectorFactory
      ->createWithPackagistOnly()
      ->willReturn($selector);
    $version_selector_factory = $this->selectorFactory->reveal();
    return new DrupalCoreVersionResolver($drupal_dot_org_api_client, $version_selector_factory);
  }

  private function expectGetCurrentToBeCalledOnce(): void {
    $this->selector
      ->findBestCandidate('drupal/core', '*', NULL, 'stable')
      ->shouldBeCalledOnce();
  }

  /**
   * @dataProvider providerVersions
   */
  public function testResolveAcceptsAllVersions($version): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0')
      ->shouldBeCalled();
    $resolver = $this->createDrupalCoreVersionResolver();

    $resolution = $resolver->resolve($version);

    /* @noinspection PhpUnitTestsInspection */
    self::assertTrue(is_string($resolution), 'Accepted version and returned string.');
  }

  public function testResolveOldestSupported(): void {
    $this->drupalDotOrgApiClient
      ->getOldestSupportedDrupalCoreBranch()
      ->willReturn('8.8.x')
      ->shouldBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('8.8.0')
      ->shouldBeCalledOnce();
    $this->selector
      ->findBestCandidate('drupal/core', '8.8.x', NULL, 'stable')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::OLDEST_SUPPORTED());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::OLDEST_SUPPORTED());

    self::assertSame('8.8.0', $actual);
  }

  public function testResolvePreviousMinor(): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '9.0.0')
      ->shouldBeCalledTimes(2);
    $this->selector
      ->findBestCandidate('drupal/core', '<9.1', NULL, 'stable')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::PREVIOUS_MINOR());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::PREVIOUS_MINOR());

    self::assertSame('9.0.0', $actual);
  }

  public function testResolveCurrent(): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0')
      ->shouldBeCalledOnce();
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::CURRENT());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::CURRENT());

    self::assertSame('9.1.0', $actual);
  }

  /**
   * @dataProvider providerResolveCurrentNoneFound
   */
  public function testResolveCurrentNoneFound($version): void {
    $this->selector
      ->findBestCandidate('drupal/core', '*', NULL, 'stable')
      ->willReturn(FALSE)
      ->shouldBeCalledOnce();
    $this->expectException(LogicException::class);
    $resolver = $this->createDrupalCoreVersionResolver();

    $resolver->resolve(DrupalCoreVersionEnum::CURRENT());
  }

  public function providerResolveCurrentNoneFound() {
    return [
      [DrupalCoreVersionEnum::CURRENT()],
      [DrupalCoreVersionEnum::CURRENT_DEV()],
    ];
  }

  public function testResolveCurrentDev(): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0')
      ->shouldBeCalledOnce();
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::CURRENT());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::CURRENT());

    self::assertSame('9.1.0', $actual);
  }

  public function testResolveNextMinor(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '9.2.0-alpha1')
      ->shouldBeCalledTimes(2);
    $this->selector->findBestCandidate('drupal/core', '>9.1.0', NULL, 'alpha')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::NEXT_MINOR());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::NEXT_MINOR());

    self::assertSame('9.2.0-alpha1', $actual);
  }

  public function testResolveNextMinorDev(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.2.x-dev');
    $this->selector->findBestCandidate('drupal/core', '>9.1.0', NULL, 'dev')
      ->willReturn($this->package->reveal());
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::NEXT_MINOR_DEV());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::NEXT_MINOR_DEV());

    self::assertSame('9.2.x-dev', $actual);
  }

  public function testResolveNextMajorLatestMinorBetaOrLater(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '10.0.0-beta1');
    $this->selector->findBestCandidate('drupal/core', '^10', NULL, 'beta')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER());

    self::assertSame('10.0.0-beta1', $actual);
  }

  public function testResolveNextMajorLatestMinorDev(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '10.0.x-dev')
      ->shouldBeCalledTimes(2);
    $this->selector->findBestCandidate('drupal/core', '^10', NULL, 'dev')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();

    $actual = $resolver->resolve(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_DEV());
    // Call again to test value caching.
    $resolver->resolve(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_DEV());

    self::assertSame('10.0.x-dev', $actual);
  }

  /**
   * @dataProvider providerResolveVersionNotFound
   */
  public function testResolveVersionNotFound($version): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', NULL);
    $this->selector
      ->findBestCandidate('drupal/core', Argument::any(), NULL, Argument::any())
      ->willReturn('9.1.0', FALSE);
    $resolver = $this->createDrupalCoreVersionResolver();
    $this->expectException(OrcaVersionNotFoundException::class);

    $resolver->resolve($version);
  }

  public function providerResolveVersionNotFound(): array {
    $data = $this->providerVersions();
    unset($data[DrupalCoreVersionEnum::CURRENT], $data[DrupalCoreVersionEnum::CURRENT_DEV]);
    return $data;
  }

}
