<?php

namespace Acquia\Orca\Tests\Console\Command\Qa;

use Acquia\Orca\Console\Command\Qa\QaDeprecatedCodeScanCommand;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Tool\DrupalCheckTool;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Tool\DrupalCheckTool $drupalCheck
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @coversDefaultClass \Acquia\Orca\Console\Command\Qa\QaDeprecatedCodeScanCommand
 */
class QaDeprecatedCodeScanCommandTest extends CommandTestBase {

  protected ObjectProphecy|PackageManager $packageManager;
  protected ObjectProphecy|DrupalCheckTool $drupalCheck;
  protected ObjectProphecy|FixturePathHandler $fixture;

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists(Argument::any())
      ->willReturn(TRUE);
    $this->drupalCheck = $this->prophesize(DrupalCheckTool::class);
  }

  protected function createCommand(): Command {
    $drupal_check = $this->drupalCheck->reveal();
    $fixture = $this->fixture->reveal();
    $package_manager = $this->packageManager->reveal();
    return new QaDeprecatedCodeScanCommand($drupal_check, $fixture, $package_manager);
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

    self::assertEquals('qa:deprecated-code-scan', $command->getName(), 'Set correct name.');
    self::assertEquals(['deprecations', 'drupal-check', 'phpstan'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals([], array_keys($arguments), 'Set correct arguments.');
    self::assertEquals(['sut', 'contrib'], array_keys($options), 'Set correct options.');
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommandHappyPath($args, $sut, $contrib): void {
    $this->drupalCheck
      ->run($sut, $contrib)
      ->shouldBeCalledOnce()
      ->willReturn(StatusCodeEnum::OK);

    $this->executeCommand($args);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [
        'args' => ['--sut' => self::VALID_PACKAGE],
        'sut' => self::VALID_PACKAGE,
        'contrib' => FALSE,
      ],
      [
        'args' => ['--contrib' => TRUE],
        'sut' => NULL,
        'contrib' => TRUE,
      ],
      [
        'args' => ['--sut' => self::VALID_PACKAGE, '--contrib' => TRUE],
        'sut' => self::VALID_PACKAGE,
        'contrib' => TRUE,
      ],
    ];
  }

  public function testCommandNothingToDo(): void {
    $this->executeCommand([]);

    $display = implode(PHP_EOL, [
      'Error: Nothing to do.',
      'Hint: Use the "--sut" and "--contrib" options to specify what to scan.',
    ]) . PHP_EOL;
    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testInvalidSut(): void {
    $this->packageManager
      ->exists(self::INVALID_PACKAGE)
      ->willReturn(FALSE);

    $this->executeCommand(['--sut' => self::INVALID_PACKAGE]);

    $display = sprintf('Error: Invalid value for "--sut" option: "%s".', self::INVALID_PACKAGE) . PHP_EOL;
    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testNoFixtureExists(): void {
    $this->fixture->exists()
      ->willReturn(FALSE);

    $this->executeCommand(['--sut' => self::VALID_PACKAGE]);

    $display = implode(PHP_EOL, [
      sprintf('Error: No fixture exists at %s.', self::FIXTURE_ROOT),
      'Hint: Use the "fixture:init" command to create one.',
    ]) . PHP_EOL;
    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

}
