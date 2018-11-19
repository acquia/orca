<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCompatibilitySniffTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TaskInterface;
use PHPUnit\Framework\TestCase;
use Acquia\Orca\StaticAnalysisRunner;

class StaticAnalysisRunnerTest extends TestCase {

  private const PATH = 'var/www/example';

  public function testRunner() {
    /** @var \Acquia\Orca\Task\ComposerValidateTask $composer_validate */
    $composer_validate = $this->setTaskExpectations(ComposerValidateTask::class);
    /** @var \Acquia\Orca\Task\PhpLintTask $php_lint */
    $php_lint = $this->setTaskExpectations(PhpLintTask::class);
    /** @var \Acquia\Orca\Task\PhpCompatibilitySniffTask $php_compatibility */
    $php_compatibility = $this->setTaskExpectations(PhpCompatibilitySniffTask::class);

    $runner = new StaticAnalysisRunner($composer_validate, $php_compatibility, $php_lint);
    $status_code = $runner->run(self::PATH);

    $this->assertInstanceOf(StaticAnalysisRunner::class, $runner);
    $this->assertEquals(StatusCodes::OK, $status_code);
  }

  protected function setTaskExpectations($class): TaskInterface {
    $task = $this->prophesize($class);
    $task->setPath(self::PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn($task);
    $task->execute()->shouldBeCalledTimes(1);
    /** @var \Acquia\Orca\Task\TaskInterface $task */
    $task = $task->reveal();
    return $task;
  }

}
