<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\CreateCommand;
use Acquia\Orca\Fixture\Destroyer;
use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Fixture\Creator;
use Acquia\Orca\Fixture\ProductData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $facade
 * @property \Prophecy\Prophecy\ObjectProphecy $fixtureCreator
 * @property \Prophecy\Prophecy\ObjectProphecy $fixtureDestroyer
 * @property \Prophecy\Prophecy\ObjectProphecy $productData
 */
class CreateCommandTest extends TestCase {

  private const FIXTURE_ROOT = '/var/www/orca-build';
  private const VALID_PACKAGE = 'drupal/lightning_api';
  private const INVALID_PACKAGE = 'invalid';

  protected function setUp() {
    $this->fixtureCreator = $this->prophesize(Creator::class);
    $this->fixtureDestroyer = $this->prophesize(Destroyer::class);
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
    $this->facade
      ->getDestroyer()
      ->shouldBeCalledTimes((int) in_array('getDestroyer', $methods_called));
    $this->fixtureDestroyer
      ->destroy()
      ->shouldBeCalledTimes((int) in_array('getDestroyer', $methods_called));
    $this->facade
      ->getCreator()
      ->shouldBeCalledTimes((int) in_array('getCreator', $methods_called));
    $this->fixtureCreator
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->fixtureCreator
      ->setSutOnly(TRUE)
      ->shouldBeCalledTimes((int) in_array('setSutOnly', $methods_called));
    $this->fixtureCreator
      ->create()
      ->shouldBeCalledTimes((int) in_array('getCreator', $methods_called));
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, [], ['exists'], StatusCodes::ERROR, sprintf("Error: Fixture already exists at %s.\nHint: Use the \"--force\" option to destroy it and proceed.\n", self::FIXTURE_ROOT)],
      [TRUE, ['-f' => TRUE], ['exists', 'getDestroyer', 'getCreator'], StatusCodes::OK, ''],
      [FALSE, [], ['exists', 'getCreator'], StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::INVALID_PACKAGE], ['isValidPackage'], StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['isValidPackage', 'exists', 'getCreator', 'setSut'], StatusCodes::OK, ''],
      [FALSE, ['--sut' => self::VALID_PACKAGE, '-o' => TRUE], ['isValidPackage', 'exists', 'getCreator', 'setSut', 'setSutOnly'], StatusCodes::OK, ''],
      [FALSE, ['-o' => TRUE], [], StatusCodes::ERROR, "Error: Cannot create a SUT-only fixture without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Creator $fixture_creator */
    $fixture_creator = $this->fixtureCreator->reveal();
    $this->facade->getCreator()
      ->willReturn($fixture_creator);
    /** @var \Acquia\Orca\Fixture\Destroyer $fixture_destroyer */
    $fixture_destroyer = $this->fixtureDestroyer->reveal();
    $this->facade->getDestroyer()
      ->willReturn($fixture_destroyer);
    /** @var \Acquia\Orca\Fixture\Facade $facade */
    $facade = $this->facade->reveal();
    /** @var \Acquia\Orca\Fixture\ProductData $product_data */
    $product_data = $this->productData->reveal();
    $application->add(new CreateCommand($facade, $product_data));
    /** @var \Acquia\Orca\Command\Fixture\CreateCommand $command */
    $command = $application->find(CreateCommand::getDefaultName());
    $this->assertInstanceOf(CreateCommand::class, $command);
    return new CommandTester($command);
  }

  private function executeCommand(CommandTester $tester, array $args = []) {
    $args = array_merge(['command' => CreateCommand::getDefaultName()], $args);
    $tester->execute($args);
  }

}
