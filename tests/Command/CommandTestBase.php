<?php

namespace Acquia\Orca\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTestBase extends TestCase {

  /**
   * Executes a given command with the command tester.
   *
   * @param \Symfony\Component\Console\Tester\CommandTester $tester
   *   The command tester.
   * @param string $command
   *   The command name.
   * @param array $args
   *   The command arguments.
   */
  protected function executeCommand(CommandTester $tester, string $command, array $args = []) {
    $args = array_merge(['command' => $command], $args);
    $tester->execute($args);
  }

}
