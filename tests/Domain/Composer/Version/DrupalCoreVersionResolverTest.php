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
    $this->package
      ->getPrettyVersion()
      ->willReturn(self::CURRENT);
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
      ->create(FALSE, Argument::any())
      ->willReturn($selector);
    $version_selector_factory = $this->selectorFactory->reveal();
    return new DrupalCoreVersionResolver($drupal_dot_org_api_client, $version_selector_factory);
  }

  private function expectGetCurrentToBeCalledOnce(): void {
    $this->selector
      ->findBestCandidate('drupal/core', '*', NULL, 'stable')
      ->shouldBeCalledOnce();
  }

  public function testExistsPredefinedTrue(): void {
    $this->selector
      ->findBestCandidate('drupal/core', Argument::any(), NULL, Argument::any())
      ->shouldBeCalledOnce()
      ->willReturn($this->package->reveal());
    $resolver = $this->createDrupalCoreVersionResolver();

    $exists = $resolver->existsPredefined($this->validVersion());

    self::assertTrue($exists);
  }

  public function testExistsPredefinedFalse(): void {
    $this->selector
      ->findBestCandidate('drupal/core', Argument::any(), NULL, Argument::any())
      ->shouldBeCalledOnce()
      ->willReturn(FALSE);
    $resolver = $this->createDrupalCoreVersionResolver();

    $exists = $resolver->existsPredefined($this->validVersion());

    self::assertFalse($exists);
  }

  /**
   * @dataProvider providerResolveArbitrary
   */
  public function testResolveArbitrary($constraint, $stability): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn($constraint);
    $package = $this->package->reveal();
    $this->selector = $this->prophesize(VersionSelector::class);
    $this->selector
      ->findBestCandidate('drupal/core', $constraint, NULL, $stability)
      ->willReturn($package);
    $resolver = $this->createDrupalCoreVersionResolver();

    $version = $resolver->resolveArbitrary($constraint, $stability);

    self::assertSame($constraint, $version, 'Resolved arbitrary version string.');
  }

  public function providerResolveArbitrary(): array {
    return [
      [
        'constraint' => 'v9.0.0',
        'stability' => 'dev',
      ],
      [
        'constraint' => 'v10.0.0',
        'stability' => 'stable',
      ],
    ];
  }

  /**
   * @dataProvider providerVersions
   */
  public function testResolvePredefinedAcceptsAllVersions($version): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0')
      ->shouldBeCalled();
    $resolver = $this->createDrupalCoreVersionResolver();

    $resolution = $resolver->resolvePredefined($version);

    /* @noinspection PhpUnitTestsInspection */
    self::assertTrue(is_string($resolution), 'Accepted version and returned string.');
  }

  public function testResolvePredefinedOldestSupported(): void {
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

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::OLDEST_SUPPORTED());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::OLDEST_SUPPORTED());

    self::assertSame('8.8.0', $actual);
  }

  public function testResolvePredefinedLatestLts(): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '8.9.7')
      ->shouldBeCalledTimes(2);
    $this->selector
      ->findBestCandidate('drupal/core', '<9', NULL, 'stable')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::LATEST_LTS());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::LATEST_LTS());

    self::assertSame('8.9.7', $actual);
  }

  public function testResolvePredefinedPreviousMinor(): void {
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

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::PREVIOUS_MINOR());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::PREVIOUS_MINOR());

    self::assertSame('9.0.0', $actual);
  }

  public function testResolvePredefinedCurrent(): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0')
      ->shouldBeCalledOnce();
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::CURRENT());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::CURRENT());

    self::assertSame('9.1.0', $actual);
  }

  /**
   * @dataProvider providerResolvePredefinedCurrentNoneFound
   */
  public function testResolvePredefinedCurrentNoneFound($version): void {
    $this->selector
      ->findBestCandidate('drupal/core', '*', NULL, 'stable')
      ->willReturn(FALSE)
      ->shouldBeCalledOnce();
    $this->expectException(\LogicException::class);
    $resolver = $this->createDrupalCoreVersionResolver();

    $resolver->resolvePredefined($version);
  }

  public function providerResolvePredefinedCurrentNoneFound() {
    return [
      [DrupalCoreVersionEnum::CURRENT()],
      [DrupalCoreVersionEnum::CURRENT_DEV()],
    ];
  }

  public function testResolvePredefinedCurrentDev(): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0')
      ->shouldBeCalledOnce();
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::CURRENT());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::CURRENT());

    self::assertSame('9.1.0', $actual);
  }

  public function testResolvePredefinedNextMinor(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '9.2.0-alpha1')
      ->shouldBeCalledTimes(2);
    $this->selector->findBestCandidate('drupal/core', '>9.1.0', NULL, 'alpha')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MINOR());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MINOR());

    self::assertSame('9.2.0-alpha1', $actual);
  }

  public function testResolvePredefinedNextMinorDev(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.2.x-dev');
    $this->selector->findBestCandidate('drupal/core', '>9.1.0', NULL, 'dev')
      ->willReturn($this->package->reveal());
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MINOR_DEV());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MINOR_DEV());

    self::assertSame('9.2.x-dev', $actual);
  }

  public function testResolvePredefinedNextMajorLatestMinorBetaOrLater(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '10.0.0-beta1');
    $this->selector->findBestCandidate('drupal/core', '^10', NULL, 'beta')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER());

    self::assertSame('10.0.0-beta1', $actual);
  }

  public function testResolvePredefinedNextMajorLatestMinorDev(): void {
    $this->expectGetCurrentToBeCalledOnce();
    $resolver = $this->createDrupalCoreVersionResolver();
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', '10.0.x-dev')
      ->shouldBeCalledTimes(2);
    $this->selector->findBestCandidate('drupal/core', '^10', NULL, 'dev')
      ->willReturn($this->package->reveal())
      ->shouldBeCalledOnce();

    $actual = $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_DEV());
    // Call again to test value caching.
    $resolver->resolvePredefined(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_DEV());

    self::assertSame('10.0.x-dev', $actual);
  }

  /**
   * @dataProvider providerResolvePredefinedVersionNotFound
   */
  public function testResolvePredefinedVersionNotFound($version): void {
    $this->package
      ->getPrettyVersion()
      ->willReturn('9.1.0', NULL);
    $this->selector
      ->findBestCandidate('drupal/core', Argument::any(), NULL, Argument::any())
      ->willReturn('9.1.0', FALSE);
    $resolver = $this->createDrupalCoreVersionResolver();
    $this->expectException(OrcaVersionNotFoundException::class);

    $resolver->resolvePredefined($version);
  }

  public function providerResolvePredefinedVersionNotFound(): array {
    $data = $this->providerVersions();
    unset($data[DrupalCoreVersionEnum::CURRENT], $data[DrupalCoreVersionEnum::CURRENT_DEV]);
    return $data;
  }

}
