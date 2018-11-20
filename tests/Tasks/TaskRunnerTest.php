<?php

namespace Acquia\Orca\Tests\Tasks;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\BehatTask;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCompatibilitySniffTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TaskInterface;
use Acquia\Orca\Task\TaskRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $composerValidateTask
 * @property \Prophecy\Prophecy\ObjectProphecy $phpLintTask
 * @property \Prophecy\Prophecy\ObjectProphecy $phpCompatibilityTask
 */
class TaskRunnerTest extends TestCase {

  private const PATH = 'var/www/example';

  private const STATUS_MESSAGE = 'Printing status message';

  public function testTaskRunner() {
    $output = $this->prophesize(SymfonyStyle::class);
    $output->section(self::STATUS_MESSAGE)
      ->shouldBeCalledTimes(2);
    /** @var \Symfony\Component\Console\Style\SymfonyStyle $output */
    $output = $output->reveal();
    /** @var \Acquia\Orca\Task\BehatTask $behat */
    $behat = $this->setTaskExpectations(BehatTask::class);
    /** @var \Acquia\Orca\Task\PhpLintTask $php_lint */
    $php_lint = $this->setTaskExpectations(PhpLintTask::class);

    $runner = new TaskRunner($output);
    $runner->setPath('foobar')
      ->addTask($behat)
      ->addTask($php_lint)
      ->setPath(self::PATH);
    $status_code = $runner->run();
    // Make sure tasks are reset on clone.
    (clone($runner))->run();

    $this->assertInstanceOf(TaskRunner::class, $runner, 'Successfully instantiated class.');
    $this->assertEquals(StatusCodes::OK, $status_code, 'Returned a "success" status code.');
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
    /** @var \Acquia\Orca\Task\TaskInterface $task */
    $task = $task->reveal();
    return $task;
  }

}
