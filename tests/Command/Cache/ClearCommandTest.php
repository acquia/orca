<?php

namespace Acquia\Orca\Tests\Command\Cache;

use Acquia\Orca\Command\Cache\ClearCommand;
use Acquia\Orca\Command\StatusCodes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $filesystem
 */
class ClearCommandTest extends TestCase {

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($cache_dir) {
    $this->filesystem
      ->remove($cache_dir)
      ->shouldBeCalledTimes(1);
    $tester = $this->createCommandTester($cache_dir);

    $this->executeCommand($tester);

    $this->assertEquals('', $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      ['/var/www/orca/var/cache/prod'],
      ['/tmp/example'],
    ];
  }

  private function createCommandTester($cache_dir): CommandTester {
    $application = new Application();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    $application->add(new ClearCommand($cache_dir, $filesystem));
    /** @var \Acquia\Orca\Command\Cache\ClearCommand $command */
    $command = $application->find(ClearCommand::getDefaultName());
    $this->assertInstanceOf(ClearCommand::class, $command);
    $this->assertEquals('cache:clear', $command->getName());
    return new CommandTester($command);
  }

  private function executeCommand(CommandTester $tester, array $args = []) {
    $args = array_merge(['command' => ClearCommand::getDefaultName()], $args);
    $tester->execute($args);
  }


}
