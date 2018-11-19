<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Tasks\ComposerValidateTask;
use Acquia\Orca\Tasks\PhpCompatibilitySniffTask;
use Acquia\Orca\Tasks\PhpLintTask;
use Acquia\Orca\Tasks\TaskInterface;
use PHPUnit\Framework\TestCase;
use Acquia\Orca\StaticAnalysisRunner;

class StaticAnalysisRunnerTest extends TestCase {

  private const PATH = 'var/www/example';

  public function testRunner() {
    /** @var \Acquia\Orca\Tasks\ComposerValidateTask $composer_validate */
    $composer_validate = $this->setTaskExpectations(ComposerValidateTask::class);
    /** @var \Acquia\Orca\Tasks\PhpLintTask $php_lint */
    $php_lint = $this->setTaskExpectations(PhpLintTask::class);
    /** @var \Acquia\Orca\Tasks\PhpCompatibilitySniffTask $php_compatibility */
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
    /** @var \Acquia\Orca\Tasks\TaskInterface $task */
    $task = $task->reveal();
    return $task;
  }

}
