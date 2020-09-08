<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Composer\VersionGuesser;
use Acquia\Orca\Console\Command\Debug\DebugGuessVersionCommand;
use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Helper\Exception\FileNotFoundException;
use Acquia\Orca\Helper\Exception\OrcaException;
use Acquia\Orca\Helper\Exception\ParseError;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Composer\VersionGuesser|\Prophecy\Prophecy\ObjectProphecy $versionGuesser
 * @coversDefaultClass \Acquia\Orca\Console\Command\Debug\DebugGuessVersionCommand
 */
class DebugGuessVersionCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  protected function setUp(): void {
    $this->versionGuesser = $this->prophesize(VersionGuesser::class);
  }

  protected function createCommand(): Command {
    $version_guesser = $this->versionGuesser->reveal();
    return new DebugGuessVersionCommand($version_guesser);
  }

  /**
   * @covers ::__construct
   * @covers ::configure
   */
  public function testBasicConfiguration(): void {
    $command = $this->createCommand();

    $definition = $command->getDefinition();
    $arguments = $definition->getArguments();
    $path_argument = $definition->getArgument('path');
    $options = $definition->getOptions();

    self::assertEquals('debug:guess-version', $command->getName(), 'Set correct name.');
    self::assertEquals(['guess'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals(['path'], array_keys($arguments), 'Set correct arguments.');
    self::assertTrue($path_argument->isRequired(), 'Required path argument.');
    self::assertEquals([], array_keys($options), 'Set correct options.');
  }

  /**
   * @dataProvider providerExecution
   */
  public function testExecution($version): void {
    $this->versionGuesser
      ->guessVersion(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn($version);

    $this->executeCommand(['path' => self::SUT_PATH]);

    self::assertEquals("{$version}\n", $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerExecution(): array {
    return [
      ['1.0.0'],
      ['dev-topic-branch'],
    ];
  }

  /**
   * @dataProvider providerExecutionWithException
   */
  public function testExecutionWithException($exception): void {
    $this->versionGuesser
      ->guessVersion(Argument::any())
      ->shouldBeCalledOnce()
      ->willThrow($exception);

    $this->executeCommand(['path' => self::SUT_PATH]);

    self::assertEquals("Error: {$exception->getMessage()}\n", $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerExecutionWithException(): array {
    return [
      [new FileNotFoundException('Lorem ipsum')],
      [new ParseError('Dolor sit')],
      [new OrcaException('Amet')],
    ];
  }

}
