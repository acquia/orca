<?php

namespace Acquia\Orca\Tests\Tasks;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Acquia\Orca\Tasks\PhpLintTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class PhpLintTaskTest extends TestCase {

  public function testTask() {
    /** @var \Symfony\Component\Finder\Finder $finder */
    $finder = $this->prophesize(Finder::class)->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->prophesize(Fixture::class)->reveal();
    /** @var \Acquia\Orca\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $task = new PhpLintTask($finder, $fixture, $process_runner);

    $this->assertInstanceOf(PhpLintTask::class, $task);
  }

}
