<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient;
use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Acquia\Orca\Tests\Enum\DrupalCoreVersionEnumsTestTrait;
use Acquia\Orca\Tests\TestCase;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient|\Prophecy\Prophecy\ObjectProphecy $drupalDotOrgApiClient
 * @property \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory|\Prophecy\Prophecy\ObjectProphecy $selectorFactory
 * @property \Composer\Package\PackageInterface|\Prophecy\Prophecy\ObjectProphecy $package
 * @property \Composer\Package\Version\VersionSelector|\Prophecy\Prophecy\ObjectProphecy $selector
 */
class WipTest extends TestCase {

  use DrupalCoreVersionEnumsTestTrait;

  protected VersionSelectorFactory|ObjectProphecy $selectorFactory;

  protected VersionSelector|ObjectProphecy $selector;

  protected function setUp(): void {
    $this->selector = $this->prophesize(VersionSelector::class);
    $this->selectorFactory = $this->prophesize(VersionSelectorFactory::class);
  }

  private function createSut(): DrupalCoreVersionResolver {
    $drupal_dot_org_api_client = $this->prophesize(DrupalDotOrgApiClient::class);
    $drupal_dot_org_api_client = $drupal_dot_org_api_client->reveal();
    $selector = $this->selector->reveal();
    $this->selectorFactory
      ->create(Argument::any(), Argument::any())
      ->willReturn($selector);
    $version_selector_factory = $this->selectorFactory->reveal();
    return new DrupalCoreVersionResolver($drupal_dot_org_api_client, $version_selector_factory);
  }

  public function testResolvePredefinedNextMinorDev(): void {
    $current_stable = '10.1.1';
    $current_dev = '10.2.x-dev';
    $next_major = '^11';
    $next_minor_dev = '11.x-dev';

    $current_stable_package = $this->prophesize(PackageInterface::class);
    $current_stable_package
      ->getPrettyVersion()
      ->willReturn($current_stable)
      ->shouldBeCalled();
    $this->selector
      ->findBestCandidate('drupal/core', '*', 'stable')
      ->willReturn($current_stable_package->reveal())
      ->shouldBeCalled();

    $current_dev_package = $this->prophesize(PackageInterface::class);
    $current_dev_package
      ->getPrettyVersion()
      ->shouldBeCalled()
      ->willReturn($current_dev);
    $this->selector
      ->findBestCandidate('drupal/core', $current_dev, 'stable')
      ->shouldBeCalled()
      ->willReturn(FALSE);

    $next_minor_dev_package = $this->prophesize(PackageInterface::class);
    $next_minor_dev_package
      ->getPrettyVersion()
      ->shouldBeCalled()
      ->willReturn($current_dev);
    $this->selector
      ->findBestCandidate('drupal/core', $next_major, 'dev')
      ->shouldBeCalled()
      ->willReturn($next_minor_dev_package->reveal());

    $sut = $this->createSut();

    $actual = $sut->resolvePredefined(DrupalCoreVersionEnum::NEXT_MINOR_DEV());
    // Call again to test value caching.
    $sut->resolvePredefined(DrupalCoreVersionEnum::NEXT_MINOR_DEV());

    self::assertSame($next_minor_dev, $actual);
  }

}
