<?php

namespace Acquia\Orca\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestBase extends TestCase {

  protected const FIXTURE_ROOT = '/var/www/orca-build';

  protected const FIXTURE_DOCROOT = '/var/www/orca-build/www';

  protected const VALID_PACKAGE = 'drupal/lightning_api';

  protected const INVALID_PACKAGE = 'invalid';

  /**
   * The command tester.
   *
   * @var \Symfony\Component\Console\Tester\CommandTester
   */
  private $commandTester;

  /**
   * Creates a command object to test.
   *
   * @return \Symfony\Component\Console\Command\Command
   *   A command object with mocked dependencies injected.
   */
  abstract protected function createCommand(): Command;

  /**
   * Executes a given command with the command tester.
   *
   * @param array $args
   *   The command arguments.
   * @param string[] $inputs
   *   An array of strings representing each input passed to the command input
   *   stream.
   */
  protected function executeCommand(array $args = [], array $inputs = []): void {
    $tester = $this->getCommandTester();
    $tester->setInputs($inputs);
    $command_name = $this->createCommand()::getDefaultName();
    $args = array_merge(['command' => $command_name], $args);
    $tester->execute($args);
  }

  /**
   * Gets the command tester.
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   *   A command tester.
   */
  protected function getCommandTester(): CommandTester {
    if ($this->commandTester) {
      return $this->commandTester;
    }

    $application = new Application();
    $created_command = $this->createCommand();
    $application->add($created_command);
    $found_command = $application->find($created_command::getDefaultName());
    $this->assertInstanceOf(get_class($created_command), $found_command, 'Instantiated class.');
    $this->commandTester = new CommandTester($found_command);
    return $this->commandTester;
  }

  /**
   * Gets the display returned by the last execution of the command.
   *
   * @return string
   *   The display.
   */
  protected function getDisplay(): string {
    return $this->getCommandTester()->getDisplay();
  }

  /**
   * Gets the status code returned by the last execution of the command.
   *
   * @return int
   *   The status code.
   */
  protected function getStatusCode(): int {
    return $this->getCommandTester()->getStatusCode();
  }

}
