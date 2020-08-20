<?php

namespace Acquia\Orca\Tests\Tool;

use Acquia\Orca\Fixture\FixtureResetter;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Package\PackageManager;
use Acquia\Orca\Server\ServerStack;
use Acquia\Orca\Tool\Phpunit\PhpUnitTask;
use Acquia\Orca\Tool\TaskInterface;
use Acquia\Orca\Tool\TestRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureResetter $fixtureResetter
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Style\SymfonyStyle $output
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Tool\Phpunit\PhpUnitTask $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Process\ProcessRunner $processRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\ServerStack $serverStack
 */
class TestRunnerTest extends TestCase {

  private const PATH = 'var/www/example';

  private const STATUS_MESSAGE = 'Printing status message';

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixtureResetter = $this->prophesize(FixtureResetter::class);
    $this->output = $this->prophesize(SymfonyStyle::class);
    $this->phpunit = $this->prophesize(PhpUnitTask::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->serverStack = $this->prophesize(ServerStack::class);
  }

  private function createTestRunner(): TestRunner {
    $filesystem = $this->filesystem->reveal();
    $fixture_resetter = $this->fixtureResetter->reveal();
    $output = $this->output->reveal();
    $phpunit = $this->phpunit->reveal();
    $package_manager = $this->packageManager->reveal();
    $server_stack = $this->serverStack->reveal();
    return new TestRunner($filesystem, $fixture_resetter, $output, $phpunit, $package_manager, $server_stack);
  }

  public function testTaskRunner(): void {
    $runner = $this->createTestRunner();

    self::assertInstanceOf(TestRunner::class, $runner, 'Instantiated class.');
  }

  protected function setTaskExpectations($class): TaskInterface {
    $task = $this->prophesize($class);
    $task->statusMessage()
      ->shouldBeCalledTimes(1)
      ->willReturn(self::STATUS_MESSAGE);
    $task->setPath(self::PATH)
      ->shouldBeCalledTimes(1);
    $task->execute()->shouldBeCalledTimes(1);
    $task = $task->reveal();
    return $task;
  }

}
