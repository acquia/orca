<?php

namespace Acquia\Orca\Tests\Task\TestFramework;

use Acquia\Orca\Fixture\FixtureResetter;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Server\ServerStack;
use Acquia\Orca\Task\TaskInterface;
use Acquia\Orca\Task\TestFramework\PhpUnitTask;
use Acquia\Orca\Task\TestFramework\TestRunner;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureResetter $fixtureResetter
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Style\SymfonyStyle $output
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\PhpUnitTask $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ProcessRunner $processRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
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

  public function testTaskRunner() {
    $runner = $this->createTestRunner();

    $this->assertInstanceOf(TestRunner::class, $runner, 'Instantiated class.');
  }

  protected function setTaskExpectations($class): TaskInterface {
    $task = $this->prophesize($class);
    $task->statusMessage()
      ->shouldBeCalledTimes(1)
      ->willReturn(self::STATUS_MESSAGE);
    $task->setPath(self::PATH)
      ->shouldBeCalledTimes(1);
    $task->execute()->shouldBeCalledTimes(1);
    /** @var \Acquia\Orca\Task\TaskInterface $task */
    $task = $task->reveal();
    return $task;
  }

  private function createTestRunner(): TestRunner {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureResetter $fixture_resetter */
    $fixture_resetter = $this->fixtureResetter->reveal();
    /** @var \Symfony\Component\Console\Style\SymfonyStyle $output */
    $output = $this->output->reveal();
    /** @var \Acquia\Orca\Task\TestFramework\PhpUnitTask $phpunit */
    $phpunit = $this->phpunit->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->processRunner->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    /** @var \Acquia\Orca\Server\ServerStack $server_stack */
    $server_stack = $this->serverStack->reveal();
    return new TestRunner($filesystem, $fixture_resetter, $output, $phpunit, $process_runner, $package_manager, $server_stack);
  }

}
