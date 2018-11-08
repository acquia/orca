<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\InitCommand;
use Acquia\Orca\Fixture\Destroyer;
use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Fixture\Creator;
use Acquia\Orca\Fixture\ProductData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

define('ORCA_FIXTURE_ROOT', '/tmp/orca-fixture-root');

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $facade
 * @property \Prophecy\Prophecy\ObjectProphecy $creator
 * @property \Prophecy\Prophecy\ObjectProphecy $destroyer
 * @property \Prophecy\Prophecy\ObjectProphecy $productData
 */
class InitCommandTest extends TestCase {

  private const FIXTURE_ROOT = '/var/www/orca-build';
  private const VALID_PACKAGE = 'drupal/lightning_api';
  private const INVALID_PACKAGE = 'invalid';

  protected function setUp() {
    $this->creator = $this->prophesize(Creator::class);
    $this->destroyer = $this->prophesize(Destroyer::class);
    $this->facade = $this->prophesize(Facade::class);
    $this->facade->exists()
      ->willReturn(FALSE);
    $this->facade->rootPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->productData = $this->prophesize(ProductData::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $status_code, $display) {
    $this->productData
      ->isValidPackage(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('isValidPackage', $methods_called))
      ->willReturn(@$args['--sut'] === self::VALID_PACKAGE);
    $this->facade
      ->exists()
      ->shouldBeCalledTimes((int) in_array('exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->destroyer
      ->destroy()
      ->shouldBeCalledTimes((int) in_array('destroy', $methods_called));
    $this->creator
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->creator
      ->setSutOnly(TRUE)
      ->shouldBeCalledTimes((int) in_array('setSutOnly', $methods_called));
    $this->creator
      ->create()
      ->shouldBeCalledTimes((int) in_array('create', $methods_called));
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, [], ['exists'], StatusCodes::ERROR, sprintf("Error: Fixture already exists at %s.\nHint: Use the \"--force\" option to destroy it and proceed.\n", self::FIXTURE_ROOT)],
      [TRUE, ['-f' => TRUE], ['exists', 'destroy', 'create'], StatusCodes::OK, ''],
      [FALSE, [], ['exists', 'create'], StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::INVALID_PACKAGE], ['isValidPackage'], StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['isValidPackage', 'exists', 'create', 'setSut'], StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['isValidPackage', 'exists', 'create', 'setSut', 'setSutOnly'], StatusCodes::OK, ''],
      [FALSE, ['--sut-only' => TRUE], [], StatusCodes::ERROR, "Error: Cannot create a SUT-only fixture without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Creator $fixture_creator */
    $fixture_creator = $this->creator->reveal();
    /** @var \Acquia\Orca\Fixture\Destroyer $fixture_destroyer */
    $fixture_destroyer = $this->destroyer->reveal();
    /** @var \Acquia\Orca\Fixture\Facade $facade */
    $facade = $this->facade->reveal();
    /** @var \Acquia\Orca\Fixture\ProductData $product_data */
    $product_data = $this->productData->reveal();
    $application->add(new InitCommand($fixture_creator, $fixture_destroyer, $facade, $product_data));
    /** @var \Acquia\Orca\Command\Fixture\InitCommand $command */
    $command = $application->find(InitCommand::getDefaultName());
    $this->assertInstanceOf(InitCommand::class, $command);
    return new CommandTester($command);
  }

  private function executeCommand(CommandTester $tester, array $args = []) {
    $args = array_merge(['command' => InitCommand::getDefaultName()], $args);
    $tester->execute($args);
  }

}
