<?php

namespace Acquia\Orca\Tests\Command\Debug;

use Acquia\Orca\Command\Debug\DebugPackagesCommand;
use Acquia\Orca\Enum\DrupalCoreVersion;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Fixture\Package;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\DrupalCoreVersionFinder $drupalCoreVersionFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Composer\Semver\VersionParser $versionParser
 */
class DebugPackagesCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->getAll()
      ->willReturn([]);
    $this->versionParser = new VersionParser();
  }

  protected function createCommand(): Command {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    return new DebugPackagesCommand($drupal_core_version_finder, $package_manager, $this->versionParser);
  }

  public function testBasicExecution() {
    /** @var \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Package $package */
    $package = $this->prophesize(Package::class);
    $package->getPackageName()->willReturn('Example 1', 'Example 2');
    $package->getType()->willReturn('drupal-module');
    $package->getInstallPathRelative()->willReturn('docroot/modules/contrib/example1', 'docroot/modules/contrib/example2');
    $package->getRepositoryUrlRaw()->willReturn('../example1', '../example2');
    $package->getVersionRecommended('*')->willReturn('~1.0');
    $package->getVersionDev('*')->willReturn('1.x-dev');
    $package->shouldGetEnabled()->willReturn(TRUE);
    $this->packageManager
      ->getAll()
      ->shouldBeCalledOnce()
      ->willReturn([$package, $package]);

    $this->executeCommand();

    $this->assertEquals(ltrim('
+-----------+---------------+--------------------- Drupal * ---+-------------+---------+-------------+--------+
| Package   | type          | install_path                     | url         | version | version_dev | enable |
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
| Example 1 | drupal-module | docroot/modules/contrib/example1 | ../example1 | ~1.0    | 1.x-dev     | yes    |
| Example 2 | drupal-module | docroot/modules/contrib/example2 | ../example2 | ~1.0    | 1.x-dev     | yes    |
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
'), $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @dataProvider providerValidArguments
   */
  public function testValidArguments($argument) {
    $version = '8.7.0.0';
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion($argument))
      ->shouldBeCalledOnce()
      ->willReturn($version);

    $this->executeCommand(['core' => $argument]);

    $this->assertContains(ltrim("- Drupal {$version} -"), $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerValidArguments() {
    $versions = DrupalCoreVersion::keys();
    array_walk($versions, function (&$value) {
      $value = [$value];
    });
    return $versions;
  }

  /**
   * @dataProvider providerInvalidArguments
   */
  public function testInvalidArguments($version) {
    $this->executeCommand(['core' => $version]);

    $error_message = sprintf('Error: Invalid value for "core" option: "%s".', $version) . PHP_EOL
      . 'Hint: Acceptable values are "PREVIOUS_RELEASE", "PREVIOUS_DEV", "CURRENT_RECOMMENDED", "CURRENT_DEV", "NEXT_RELEASE", "NEXT_DEV", "D9_READINESS", or any version string Composer understands.' . PHP_EOL;
    $this->assertEquals($error_message, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerInvalidArguments() {
    return [
      ['invalid'],
      [FALSE],
    ];
  }

}
