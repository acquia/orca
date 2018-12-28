<?php

namespace Acquia\Orca\Tests\Utility;

use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Server\ServerStack;
use Acquia\Orca\Task\TestFramework\BehatTask;
use Acquia\Orca\Task\TestFramework\PhpUnitTask;
use Acquia\Orca\Task\TestFramework\TestRunner;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\BehatTask $behat
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder $finder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Style\SymfonyStyle $output
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\PhpUnitTask $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ProcessRunner $processRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\ServerStack $serverStack
 */
class TestRunnerTest extends TestCase {

  public function setUp() {
    $this->behat = $this->prophesize(BehatTask::class);
    $this->finder = $this->prophesize(Finder::class);
    $this->output = $this->prophesize(SymfonyStyle::class);
    $this->phpunit = $this->prophesize(PhpUnitTask::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->getMultiple()
      ->willReturn([]);
    $this->serverStack = $this->prophesize(ServerStack::class);
  }

  public function testTestRunner() {
    $this->serverStack
      ->start()
      ->shouldBeCalledTimes(1);
    $this->serverStack
      ->stop()
      ->shouldBeCalledTimes(1);
    $test_runner = $this->createTestRunner();

    $test_runner->run();

    $this->assertTrue($test_runner instanceof TestRunner, 'Instantiated class.');
  }

  protected function createTestRunner(): TestRunner {
    /** @var \Acquia\Orca\Task\TestFramework\BehatTask $behat */
    $behat = $this->behat->reveal();
    /** @var \Symfony\Component\Finder\Finder $finder */
    $finder = $this->finder->reveal();
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
    return new TestRunner($behat, $finder, $output, $phpunit, $process_runner, $package_manager, $server_stack);
  }

}
