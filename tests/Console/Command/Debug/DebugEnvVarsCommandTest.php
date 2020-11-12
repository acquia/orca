<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugEnvVarsCommand;
use Acquia\Orca\Console\Command\Debug\Helper\EnvVarsTableBuilder;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @property \Acquia\Orca\Console\Command\Debug\Helper\EnvVarsTableBuilder|\Prophecy\Prophecy\ObjectProphecy $tableBuilder
 * @coversDefaultClass \Acquia\Orca\Console\Command\Debug\DebugEnvVarsCommand
 */
class DebugEnvVarsCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->tableBuilder = $this->prophesize(EnvVarsTableBuilder::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function createCommand(): Command {
    $table_builder = $this->tableBuilder->reveal();
    return new DebugEnvVarsCommand($table_builder);
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

    self::assertEquals('debug:env-vars', $command->getName(), 'Set correct name.');
    self::assertEquals(['env', 'vars'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals([], array_keys($arguments), 'Set correct arguments.');
    self::assertEquals([], array_keys($options), 'Set correct options.');
  }

  /**
   * @dataProvider providerOutput
   */
  public function testOutput($table_rows): void {
    $output = new BufferedOutput();
    $table = (new Table($output))
      ->setRows($table_rows);
    $this->tableBuilder
      ->build(Argument::any())
      ->willReturn($table)
      ->shouldBeCalledOnce();
    $table->render();

    $this->executeCommand();

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerOutput(): array {
    return [
      ['table_rows' => [['Lorem', '~', 'Ipsum']]],
      ['table_rows' => [['Dolor', '~', 'Sit']]],
    ];
  }

}
