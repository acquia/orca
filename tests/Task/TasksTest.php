<?php

namespace Acquia\Orca\Tests\Task;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Task\TestFramework\BehatTask;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCompatibilitySniffTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TestFramework\PhpUnitTask;
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
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    $project_dir = '/var/www/orca';

    $object = new $class($finder, $fixture, $process_runner, $project_dir);

    $this->assertInstanceOf($class, $object, sprintf('Successfully instantiated class: %s.', $class));
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
