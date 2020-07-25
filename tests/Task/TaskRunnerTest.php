<?php

namespace Acquia\Orca\Tests\Task;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask;
use Acquia\Orca\Task\TaskInterface;
use Acquia\Orca\Task\TaskRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composerValidateTask
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $phplintTask
 */
class TaskRunnerTest extends TestCase {

  private const PATH = 'var/www/example';

  private const STATUS_MESSAGE = 'Printing status message';

  public function testTaskRunner() {
    $output = $this->prophesize(SymfonyStyle::class);
    $output->section(self::STATUS_MESSAGE)
      ->shouldBeCalledTimes(1);
    $output->success('Passed')
      ->shouldBeCalledTimes(2);
    $output = $output->reveal();
    $phplint = $this->setTaskExpectations(PhpLintTask::class);

    $runner = new TaskRunner($output);
    $runner->setPath('foobar')
      ->addTask($phplint)
      ->setPath(self::PATH);
    $status_code = $runner->run();
    // Make sure tasks are reset on clone.
    (clone($runner))->run();

    $this->assertInstanceOf(TaskRunner::class, $runner, 'Instantiated class.');
    $this->assertEquals(StatusCode::OK, $status_code, 'Returned a "success" status code.');
  }

  protected function setTaskExpectations($class): TaskInterface {
    $task = $this->prophesize($class);
    $task->statusMessage()
      ->shouldBeCalledTimes(1)
      ->willReturn(self::STATUS_MESSAGE);
    $task->setPath(self::PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn($task);
    $task->execute()->shouldBeCalledTimes(1);
    $task = $task->reveal();
    return $task;
  }

}
