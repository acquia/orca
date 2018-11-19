<?php

namespace Acquia\Orca\Tests\Tasks;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Acquia\Orca\Tasks\BehatTask;
use Acquia\Orca\Tasks\ComposerValidateTask;
use Acquia\Orca\Tasks\PhpCompatibilitySniffTask;
use Acquia\Orca\Tasks\PhpLintTask;
use Acquia\Orca\Tasks\PhpUnitTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class TasksTest extends TestCase {

  /**
   * @dataProvider providerConstruction
   */
  public function testConstruction($class) {
    /** @var \Symfony\Component\Finder\Finder $finder */
    $finder = $this->prophesize(Finder::class)->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->prophesize(Fixture::class)->reveal();
    /** @var \Acquia\Orca\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $task = new $class($finder, $fixture, $process_runner);

    $this->assertInstanceOf($class, $task);
  }

  public function providerConstruction() {
    return [
      [BehatTask::class],
      [ComposerValidateTask::class],
      [PhpCompatibilitySniffTask::class],
      [PhpLintTask::class],
      [PhpUnitTask::class],
    ];
  }

}
