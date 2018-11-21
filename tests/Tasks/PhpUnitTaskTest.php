<?php

namespace Acquia\Orca\Tests\Tasks;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Acquia\Orca\Task\PhpUnitTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class PhpUnitTaskTest extends TestCase {

  public function testConstruction() {
    /** @var \Symfony\Component\Finder\Finder $finder */
    $finder = $this->prophesize(Finder::class)->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->prophesize(Fixture::class)->reveal();
    /** @var \Acquia\Orca\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    $project_dir = '/var/www/orca';

    $task = new PhpUnitTask($finder, $fixture, $project_dir, $process_runner);

    $this->assertInstanceOf(PhpUnitTask::class, $task);
  }

}
