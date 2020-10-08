<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugCoreVersionsCommand;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionResolver
 * @coversDefaultClass \Acquia\Orca\Console\Command\Debug\DebugCoreVersionsCommand
 */
class DebugCoreVersionsCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->drupalCoreVersionResolver = $this->prophesize(DrupalCoreVersionResolver::class);
  }

  protected function createCommand(): Command {
    $drupal_core_version_resolver = $this->drupalCoreVersionResolver->reveal();
    return new DebugCoreVersionsCommand($drupal_core_version_resolver);
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
