<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugPackagesCommand;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Acquia\Orca\Tests\Enum\DrupalCoreVersionEnumsTestTrait;
use Composer\Semver\VersionParser;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupalCoreVersionFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Composer\Semver\VersionParser $versionParser
 */
class DebugPackagesCommandTest extends CommandTestBase {

  use DrupalCoreVersionEnumsTestTrait;

  protected function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->getCompanyPackages()
      ->willReturn([]);
    $this->versionParser = new VersionParser();
  }

  protected function createCommand(): Command {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new DebugPackagesCommand($drupal_core_version_finder, $package_manager, $this->versionParser);
  }

  public function testBasicExecution(): void {
    $package = $this->prophesize(Package::class);
    $package->getPackageName()->willReturn('Example 1', 'Example 2');
    $package->getType()->willReturn('drupal-module');
    $package->getInstallPathRelative()->willReturn('docroot/modules/contrib/example1', 'docroot/modules/contrib/example2');
    $package->getRepositoryUrlRaw()->willReturn('../example1', '../example2');
    $package->getVersionRecommended('*')->willReturn('~1.0');
    $package->getVersionDev('*')->willReturn('1.x-dev');
    $package->shouldGetEnabled()->willReturn(TRUE);
    $this->packageManager
      ->getCompanyPackages()
      ->shouldBeCalledOnce()
      ->willReturn([$package, $package]);

    $this->executeCommand();

    self::assertEquals(ltrim('
+-----------+---------------+--------------------- Drupal * ---+-------------+---------+-------------+--------+
| Package   | type          | install_path                     | url         | version | version_dev | enable |
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
| Example 1 | drupal-module | docroot/modules/contrib/example1 | ../example1 | ~1.0    | 1.x-dev     | yes    |
| Example 2 | drupal-module | docroot/modules/contrib/example2 | ../example2 | ~1.0    | 1.x-dev     | yes    |
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
'), $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @dataProvider providerVersions
   */
  public function testValidArguments($argument): void {
    $version = '8.7.0.0';
    $this->drupalCoreVersionFinder
      ->resolvePredefined(Argument::any())
      ->shouldBeCalledOnce()
      ->willReturn($version);

    $this->executeCommand(['core' => $argument->getKey()]);

    self::assertStringContainsString(ltrim("- Drupal {$version} -"), $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @dataProvider providerInvalidArguments
   */
  public function testInvalidArguments($version): void {
    $this->executeCommand(['core' => $version]);

    $error_message = sprintf('Error: Invalid value for "core" option: "%s".', $version) . PHP_EOL
      . sprintf('Hint: Acceptable values are "%s", or any version string Composer understands.', implode('", "', DrupalCoreVersionEnum::values())) . PHP_EOL;
    self::assertEquals($error_message, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerInvalidArguments(): array {
    return [
      ['invalid'],
      [FALSE],
    ];
  }

}
