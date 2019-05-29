<?php

namespace Acquia\Orca\Tests\Command\Debug;

use Acquia\Orca\Command\Debug\DebugPackagesCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Package;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 */
class DebugPackagesCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  public function testCommand() {
    /** @var \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Package $package */
    $package = $this->prophesize(Package::class);
    $package->getPackageName()->willReturn('Example 1', 'Example 2');
    $package->getType()->willReturn('drupal-module');
    $package->getInstallPathRelative()->willReturn('docroot/modules/contrib/example1', 'docroot/modules/contrib/example2');
    $package->getRepositoryUrl()->willReturn('../example1', '../example2');
    $package->getVersionRecommended()->willReturn('~1.0');
    $package->getVersionDev()->willReturn('1.x-dev');
    $package->shouldGetEnabled()->willReturn(TRUE);
    $this->packageManager
      ->getMultiple()
      ->shouldBeCalledTimes(1)
      ->willReturn([$package, $package]);
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, DebugPackagesCommand::getDefaultName());

    $this->assertEquals(ltrim("
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
| Package   | type          | install_path                     | url         | version | version_dev | enable |
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
| Example 1 | drupal-module | docroot/modules/contrib/example1 | ../example1 | ~1.0    | 1.x-dev     | yes    |
| Example 2 | drupal-module | docroot/modules/contrib/example2 | ../example2 | ~1.0    | 1.x-dev     | yes    |
+-----------+---------------+----------------------------------+-------------+---------+-------------+--------+
"), $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $tester->getStatusCode(), 'Returned correct status code.');
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    $application->add(new DebugPackagesCommand($package_manager));
    /** @var \Acquia\Orca\Command\Debug\DebugPackagesCommand $command */
    $command = $application->find(DebugPackagesCommand::getDefaultName());
    $this->assertInstanceOf(DebugPackagesCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
