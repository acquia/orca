<?php

namespace Acquia\Orca\Tests\Task\StaticAnalysisTool;

use Acquia\Orca\Facade\PhplocFacade;
use Acquia\Orca\Task\StaticAnalysisTool\PhplocTask;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Facade\PhplocFacade|\Prophecy\Prophecy\ObjectProphecy $phploc
 */
class PhplocTaskTest extends TestCase {

  protected function setUp() {
    $this->phploc = $this->prophesize(PhplocFacade::class);
  }

  private function createPhplocTask(): PhplocTask {
    $phploc_facade = $this->phploc->reveal();
    return new PhplocTask($phploc_facade);
  }

  /**
   * @dataProvider providerTask
   */
  public function testTask(string $path) {
    $this->phploc
      ->execute($path)
      ->shouldBeCalledOnce();

    $task = $this->createPhplocTask();
    $task->setPath($path);
    $task->execute();

    $provides_label = !empty($task->label()) && is_string($task->label());
    self::assertTrue($provides_label, 'Provides a label.');
    $provides_status_message = !empty($task->statusMessage()) && is_string($task->statusMessage());
    self::assertTrue($provides_status_message, 'Provides a status message.');
  }

  public function providerTask() {
    return [
      ['/var/www'],
      ['/test/example'],
    ];
  }

}
