<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugCoreVersionsCommand;
use Acquia\Orca\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @coversDefaultClass \Acquia\Orca\Console\Command\Debug\DebugCoreVersionsCommand
 */
class DebugCoreVersionsCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
  }

  protected function createCommand(): Command {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    return new DebugCoreVersionsCommand($drupal_core_version_finder);
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
    self::assertEquals([], array_keys($options), 'Set correct options.');
  }

}
