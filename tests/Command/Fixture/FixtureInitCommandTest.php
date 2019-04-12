<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureInitCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\DrupalCoreVersionFinder $drupalCoreVersionFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureCreator $fixtureCreator
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureRemover $fixtureRemover
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 */
class FixtureInitCommandTest extends CommandTestBase {

  private const DRUPAL_CORE_RECOMMENDED_VERSION = '8.6.14.0';

  private const DRUPAL_CORE_DEV_VERSION = '8.6.x-dev';

  protected function setUp() {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->fixtureCreator = $this->prophesize(FixtureCreator::class);
    $this->fixtureRemover = $this->prophesize(FixtureRemover::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(FALSE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $drupal_core_version, $exception, $status_code, $display) {
    $this->packageManager
      ->exists(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('PackageManager::exists', $methods_called))
      ->willReturn(@$args['--sut'] === self::VALID_PACKAGE);
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('Fixture::exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->fixtureRemover
      ->remove()
      ->shouldBeCalledTimes((int) in_array('remove', $methods_called));
    $this->drupalCoreVersionFinder
      ->getPreviousMinorVersion()
      ->shouldBeCalledTimes((int) in_array('getPreviousMinorVersion', $methods_called))
      ->willReturn($drupal_core_version);
    $this->drupalCoreVersionFinder
      ->getCurrentRecommendedVersion()
      ->shouldBeCalledTimes((int) in_array('getCurrentRecommendedVersion', $methods_called))
      ->willReturn(self::DRUPAL_CORE_RECOMMENDED_VERSION);
    $this->drupalCoreVersionFinder
      ->getCurrentDevVersion()
      ->shouldBeCalledTimes((int) in_array('getCurrentDevVersion', $methods_called))
      ->willReturn($drupal_core_version);
    $this->drupalCoreVersionFinder
      ->getLatestPreReleaseVersion()
      ->shouldBeCalledTimes((int) in_array('getLatestPreReleaseVersion', $methods_called))
      ->willReturn($drupal_core_version);
    $this->fixtureCreator
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->fixtureCreator
      ->setSutOnly(TRUE)
      ->shouldBeCalledTimes((int) in_array('setSutOnly', $methods_called));
    $this->fixtureCreator
      ->setDev(TRUE)
      ->shouldBeCalledTimes((int) in_array('setDev', $methods_called));
    $this->fixtureCreator
      ->setCoreVersion($drupal_core_version ?: self::DRUPAL_CORE_RECOMMENDED_VERSION)
      ->shouldBeCalledTimes((int) in_array('setCoreVersion', $methods_called));
    $this->fixtureCreator
      ->setSqlite(FALSE)
      ->shouldBeCalledTimes((int) in_array('setSqlite', $methods_called));
    $this->fixtureCreator
      ->setProfile((@$args['--profile']) ?: 'minimal')
      ->shouldBeCalledTimes((int) in_array('setProfile', $methods_called));
    $this->fixtureCreator
      ->setInstallSite(FALSE)
      ->shouldBeCalledTimes((int) in_array('setInstallSite', $methods_called));
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes((int) in_array('create', $methods_called));
    if ($exception) {
      $this->fixtureCreator
        ->create()
        ->willThrow(OrcaException::class);
    }
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, FixtureInitCommand::getDefaultName(), $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, [], ['Fixture::exists'], NULL, 0, StatusCodes::ERROR, sprintf("Error: Fixture already exists at %s.\nHint: Use the \"--force\" option to remove it and proceed.\n", self::FIXTURE_ROOT)],
      [TRUE, ['-f' => TRUE], ['Fixture::exists', 'remove', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], NULL, 0, StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'create', 'setSut'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'create', 'setSut', 'setSutOnly'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--core' => 'PREVIOUS_MINOR'], ['Fixture::exists', 'getPreviousMinorVersion', 'setCoreVersion', 'create'], '8.5.14.0', 0, StatusCodes::OK, ''],
      [FALSE, ['--core' => 'CURRENT_RECOMMENDED'], ['Fixture::exists', 'getCurrentRecommendedVersion', 'setCoreVersion', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--core' => 'CURRENT_DEV'], ['Fixture::exists', 'getCurrentDevVersion', 'setCoreVersion', 'create'], '8.5.x-dev', 0, StatusCodes::OK, ''],
      [FALSE, ['--core' => 'LATEST_PRERELEASE'], ['Fixture::exists', 'getLatestPreReleaseVersion', 'setCoreVersion', 'create'], '8.7.0.0-beta2', 0, StatusCodes::OK, ''],
      [FALSE, ['--core' => 'CURRENT_RECOMMENDED', '--dev' => TRUE], ['Fixture::exists', 'setDev', 'getCurrentRecommendedVersion', 'setCoreVersion', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--dev' => TRUE], ['Fixture::exists', 'setDev', 'getCurrentDevVersion', 'setCoreVersion', 'create'], self::DRUPAL_CORE_DEV_VERSION, 0, StatusCodes::OK, ''],
      [FALSE, ['--no-site-install' => TRUE], ['Fixture::exists', 'setInstallSite', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--no-sqlite' => TRUE], ['Fixture::exists', 'setSqlite', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, ['--profile' => 'lightning'], ['Fixture::exists', 'setProfile', 'create'], NULL, 0, StatusCodes::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create'], NULL, 1, StatusCodes::ERROR, ''],
      [FALSE, ['--sut-only' => TRUE], [], NULL, 0, StatusCodes::ERROR, "Error: Cannot create a SUT-only fixture without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Utility\DrupalCoreVersionFinder $drupal_core_version_finder */
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureCreator $fixture_creator */
    $fixture_creator = $this->fixtureCreator->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureRemover $fixture_remover */
    $fixture_remover = $this->fixtureRemover->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    $application->add(new FixtureInitCommand($drupal_core_version_finder, $fixture, $fixture_creator, $fixture_remover, $package_manager));
    /** @var \Acquia\Orca\Command\Fixture\FixtureInitCommand $command */
    $command = $application->find(FixtureInitCommand::getDefaultName());
    $this->assertInstanceOf(FixtureInitCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
