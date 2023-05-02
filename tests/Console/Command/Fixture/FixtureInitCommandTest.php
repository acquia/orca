<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureInitCommand;
use Acquia\Orca\Domain\Fixture\FixtureCreator;
use Acquia\Orca\Domain\Fixture\FixtureRemover;
use Acquia\Orca\Domain\Fixture\SutPreconditionsTester;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use Acquia\Orca\Options\FixtureOptionsFactory;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Domain\Fixture\FixtureCreator|\Prophecy\Prophecy\ObjectProphecy $fixtureCreator
 * @property \Acquia\Orca\Options\FixtureOptionsFactory|\Prophecy\Prophecy\ObjectProphecy $fixtureOptionsFactory
 * @property \Acquia\Orca\Options\FixtureOptions|\Prophecy\Prophecy\ObjectProphecy $fixtureOptions
 * @property \Acquia\Orca\Domain\Fixture\FixtureRemover|\Prophecy\Prophecy\ObjectProphecy $fixtureRemover
 * @property \Acquia\Orca\Domain\Fixture\SutPreconditionsTester|\Prophecy\Prophecy\ObjectProphecy $sutPreconditionsTester
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 *
 * @coversDefaultClass \Acquia\Orca\Console\Command\Fixture\FixtureInitCommand
 */
class FixtureInitCommandTest extends CommandTestBase {

  protected FixtureCreator|ObjectProphecy $fixtureCreator;
  protected FixtureOptionsFactory|ObjectProphecy $fixtureOptionsFactory;
  protected FixtureOptions|ObjectProphecy $fixtureOptions;
  protected FixtureRemover|ObjectProphecy $fixtureRemover;
  protected SutPreconditionsTester|ObjectProphecy $sutPreconditionsTester;
  protected FixturePathHandler|ObjectProphecy $fixture;

  protected function setUp(): void {
    $this->fixtureCreator = $this->prophesize(FixtureCreator::class);
    $this->fixtureOptions = $this->prophesize(FixtureOptions::class);
    $this->fixtureOptionsFactory = $this->prophesize(FixtureOptionsFactory::class);
    $this->fixtureRemover = $this->prophesize(FixtureRemover::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(FALSE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->sutPreconditionsTester = $this->prophesize(SutPreconditionsTester::class);
  }

  protected function createCommand(): Command {
    $fixture_creator = $this->fixtureCreator->reveal();
    $fixture_remover = $this->fixtureRemover->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    $fixture_options_factory = $this->fixtureOptionsFactory->reveal();
    $sut_preconditions_tester = $this->sutPreconditionsTester->reveal();
    return new FixtureInitCommand($fixture_options_factory, $fixture_path_handler, $fixture_creator, $fixture_remover, $sut_preconditions_tester);
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

    self::assertEquals('fixture:init', $command->getName(), 'Set correct name.');
    self::assertEquals(['init'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals([], array_keys($arguments), 'Set correct arguments.');
    self::assertEquals([
      'force',
      'sut',
      'sut-only',
      'bare',
      'core',
      'dev',
      'profile',
      'project-template',
      'ignore-patch-failure',
      'no-sqlite',
      'no-site-install',
      'prefer-source',
      'symlink-all',
    ], array_keys($options), 'Set correct options.');
    self::assertEquals('f', $options['force']->getShortcut(), 'Set shortcut for force option.');
  }

  public function testSuccess(): void {
    $this->fixtureOptionsFactory
      ->create(Argument::any())
      ->willReturn($this->fixtureOptions->reveal());
    $this->fixtureCreator
      ->create(Argument::any())
      ->shouldBeCalledOnce();

    $this->executeCommand();

    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
  }

  public function testFailure(): void {
    $message = 'Failed to create fixture.';
    $this->fixtureOptionsFactory
      ->create(Argument::any())
      ->willReturn($this->fixtureOptions->reveal());
    $this->fixtureCreator
      ->create(Argument::any())
      ->shouldBeCalledOnce()
      ->willThrow(new OrcaException($message));

    $this->executeCommand();

    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
    self::assertStringContainsString("[ERROR] {$message}", $this->getDisplay(), 'Displayed correct output.');
  }

  public function testInvalidOptions(): void {
    $message = 'Invalid options';
    $this->fixtureOptionsFactory
      ->create(Argument::any())
      ->shouldBeCalledOnce()
      ->willThrow(new OrcaInvalidArgumentException($message));

    $this->executeCommand();

    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
    self::assertEquals("Error: {$message}" . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
  }

}
