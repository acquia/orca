<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugCiJobsCommand;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Acquia\Orca\Console\Command\Debug\DebugCiJobsCommand
 */
class DebugCiJobsCommandTest extends CommandTestBase {

  protected function createCommand(): Command {
    return new DebugCiJobsCommand();
  }

  /**
   * @covers ::__construct
   * @covers ::configure
   */
  public function testBasicConfiguration(): void {
    $command = $this->createCommand();

    $definition = $command->getDefinition();
    $arguments = $definition->getArguments();
    $options = $definition->getOptions();

    self::assertEquals('debug:ci-jobs', $command->getName(), 'Set correct name.');
    self::assertEquals(['jobs'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals([], array_keys($arguments), 'Set correct arguments.');
    self::assertEquals([], array_keys($options), 'Set correct options.');
  }

  public function testExecution(): void {
    $this->executeCommand();

    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @covers ::execute
   * @covers ::getHeaders
   * @covers ::getRows
   */
  public function testTableOutput(): void {
    $application = new Application();
    $application->add(new class() extends DebugCiJobsCommand {

      public function __construct() {
        parent::__construct(DebugCiJobsCommand::getDefaultName());
      }

      protected function getDescriptions(): array {
        return [
          'Test' => 'Lorem ipsum',
          'Example' => 'Dolor sit amet',
        ];
      }

    });
    $found_command = $application->find(DebugCiJobsCommand::getDefaultName());
    $tester = new CommandTester($found_command);

    $tester->execute([]);

    $output = implode(PHP_EOL, [
      '+---+---------+----------------+',
      '| # | Job     | Description    |',
      '+---+---------+----------------+',
      '| 1 | Test    | Lorem ipsum    |',
      '| 2 | Example | Dolor sit amet |',
      '+---+---------+----------------+',
    ]) . PHP_EOL;
    self::assertEquals($output, $tester->getDisplay(), 'Displayed correct output.');
  }

}
