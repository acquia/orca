<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\InitCommand;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\ProjectManager;
use Acquia\Orca\Fixture\Remover;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Creator;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Creator $creator
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Remover $remover
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\ProjectManager $projectManager
 */
class InitCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->creator = $this->prophesize(Creator::class);
    $this->remover = $this->prophesize(Remover::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(FALSE);
    $this->fixture->rootPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->projectManager = $this->prophesize(ProjectManager::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $exception, $status_code, $display) {
    $this->projectManager
      ->exists(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('ProjectManager::exists', $methods_called))
      ->willReturn(@$args['--sut'] === self::VALID_PACKAGE);
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('Fixture::exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->remover
      ->remove()
      ->shouldBeCalledTimes((int) in_array('remove', $methods_called));
    $this->creator
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->creator
      ->setSutOnly(TRUE)
      ->shouldBeCalledTimes((int) in_array('setSutOnly', $methods_called));
    $this->creator
      ->create()
      ->shouldBeCalledTimes((int) in_array('create', $methods_called));
    if ($exception) {
      $this->creator
        ->create()
        ->willThrow(OrcaException::class);
    }
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, InitCommand::getDefaultName(), $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, [], ['Fixture::exists'], 0, StatusCodes::ERROR, sprintf("Error: Fixture already exists at %s.\nHint: Use the \"--force\" option to remove it and proceed.\n", self::FIXTURE_ROOT)],
      [TRUE, ['-f' => TRUE], ['Fixture::exists', 'remove', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create'], 0, StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::INVALID_PACKAGE], ['ProjectManager::exists'], 0, StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['ProjectManager::exists', 'Fixture::exists', 'create', 'setSut'], 0, StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['ProjectManager::exists', 'Fixture::exists', 'create', 'setSut', 'setSutOnly'], 0, StatusCodes::OK, ''],
      [FALSE, [], ['Fixture::exists', 'create'], 1, StatusCodes::ERROR, ''],
      [FALSE, ['--sut-only' => TRUE], [], 0, StatusCodes::ERROR, "Error: Cannot create a SUT-only fixture without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Creator $fixture_creator */
    $fixture_creator = $this->creator->reveal();
    /** @var \Acquia\Orca\Fixture\Remover $fixture_remover */
    $fixture_remover = $this->remover->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\ProjectManager $project_manager */
    $project_manager = $this->projectManager->reveal();
    $application->add(new InitCommand($fixture_creator, $fixture, $project_manager, $fixture_remover));
    /** @var \Acquia\Orca\Command\Fixture\InitCommand $command */
    $command = $application->find(InitCommand::getDefaultName());
    $this->assertInstanceOf(InitCommand::class, $command, 'Successfully instantiated class.');
    return new CommandTester($command);
  }

}
