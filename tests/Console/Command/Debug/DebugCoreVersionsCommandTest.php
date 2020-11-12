<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugCoreVersionsCommand;
use Acquia\Orca\Console\Command\Debug\Helper\CoreVersionsTableBuilder;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @property \Acquia\Orca\Console\Command\Debug\Helper\CoreVersionsTableBuilder|\Prophecy\Prophecy\ObjectProphecy $coreVersionsTableBuilder
 * @coversDefaultClass \Acquia\Orca\Console\Command\Debug\DebugCoreVersionsCommand
 */
class DebugCoreVersionsCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $table = new Table(new NullOutput());
    $this->coreVersionsTableBuilder = $this->prophesize(CoreVersionsTableBuilder::class);
    $this->coreVersionsTableBuilder
      ->build(Argument::any(), Argument::any(), Argument::any())
      ->willReturn($table);
  }

  protected function createCommand(): Command {
    $core_versions_table_builder = $this->coreVersionsTableBuilder->reveal();
    return new DebugCoreVersionsCommand($core_versions_table_builder);
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

    self::assertEquals('debug:core-versions', $command->getName(), 'Set correct name.');
    self::assertEquals(['core'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals([], array_keys($arguments), 'Set correct arguments.');
    self::assertEquals(['examples', 'resolve'], array_keys($options), 'Set correct options.');
  }

  /**
   * @dataProvider providerOutput
   */
  public function testOutput($notice, $table_rows, $args, $include_examples, $include_resolve): void {
    $output = new BufferedOutput();
    $table = (new Table($output))
      ->setRows($table_rows);
    $this->coreVersionsTableBuilder
      ->build(Argument::any(), $include_examples, $include_resolve)
      ->willReturn($table)
      ->shouldBeCalledOnce();
    $table->render();
    $display = $notice;

    $this->executeCommand($args);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerOutput(): array {
    $notice = 'Getting version data via Composer. This can take a while.' . PHP_EOL;
    return [
      [
        'notice' => $notice,
        'table_rows' => [['Lorem']],
        'args' => [
          '--examples' => TRUE,
          '--resolve' => TRUE,
        ],
        'include_examples' => TRUE,
        'include_resolve' => TRUE,
      ],
      [
        'notice' => '',
        'table_rows' => [['Lorem', 'Ipsum']],
        'args' => [
          '--examples' => TRUE,
          '--resolve' => FALSE,
        ],
        'include_examples' => TRUE,
        'include_resolve' => FALSE,
      ],
      [
        'notice' => $notice,
        'table_rows' => [['Lorem', 'Ipsum', 'Dolor']],
        'args' => [
          '--examples' => FALSE,
          '--resolve' => TRUE,
        ],
        'include_examples' => FALSE,
        'include_resolve' => TRUE,
      ],
      [
        'notice' => '',
        'table_rows' => [['Lorem', 'Ipsum', 'Dolor', 'Sit']],
        'args' => [
          '--examples' => FALSE,
          '--resolve' => FALSE,
        ],
        'include_examples' => FALSE,
        'include_resolve' => FALSE,
      ],
    ];
  }

}
