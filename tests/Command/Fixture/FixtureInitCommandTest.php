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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureCreator $fixtureCreator
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureRemover $fixtureRemover
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 */
class FixtureInitCommandTest extends CommandTestBase {

  private const DRUPAL_CORE_VERSION = '8.6.0';

  protected function setUp() {
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
  public function testCommand($fixture_exists, $args, $methods_called, $exception, $status_code, $display) {
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
      ->setCoreVersion(self::DRUPAL_CORE_VERSION)
      ->shouldBeCalledTimes((int) in_array('setCoreVersion', $methods_called));
    $this->fixtureCreator
      ->setSqlite(FALSE)
      ->shouldBeCalledTimes((int) in_array('setSqlite', $methods_called));
    $this->fixtureCreator
      ->setProfile((@$args['--profile']) ?: 'minimal')
      ->shouldBeCalledTimes((int) in_array('setProfile', $methods_called));
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
      [TRUE, [], ['Fixture::exists'], 0, StatusCodes::ERROR, sprintf("Error: Fixture already exists at %s.\nHint: Use the \"--force\" option to remove it and proceed.\n", self::FIXTURE_ROOT)],
      [TRUE, ['-f' => TRUE], ['Fixture::exists', 'remove', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], 0, StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'create', 'setSut'], 0, StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'create', 'setSut', 'setSutOnly'], 0, StatusCodes::OK, ''],
      [FALSE, ['--core' => self::DRUPAL_CORE_VERSION], ['Fixture::exists', 'setCoreVersion', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, ['--dev' => TRUE], ['Fixture::exists', 'setDev', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, ['--no-sqlite' => TRUE], ['Fixture::exists', 'setSqlite', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, ['--profile' => 'lightning'], ['Fixture::exists', 'setProfile', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create'], 1, StatusCodes::ERROR, ''],
      [FALSE, ['--sut-only' => TRUE], [], 0, StatusCodes::ERROR, "Error: Cannot create a SUT-only fixture without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\FixtureCreator $fixture_creator */
    $fixture_creator = $this->fixtureCreator->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureRemover $fixture_remover */
    $fixture_remover = $this->fixtureRemover->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    $application->add(new FixtureInitCommand($fixture, $fixture_creator, $fixture_remover, $package_manager));
    /** @var \Acquia\Orca\Command\Fixture\FixtureInitCommand $command */
    $command = $application->find(FixtureInitCommand::getDefaultName());
    $this->assertInstanceOf(FixtureInitCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
