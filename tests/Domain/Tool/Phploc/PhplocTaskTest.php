<?php

namespace Acquia\Orca\Tests\Domain\Tool\Phploc;

use Acquia\Orca\Domain\Tool\Phploc\Phploc;
use Acquia\Orca\Domain\Tool\Phploc\PhplocTask;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Domain\Tool\Phploc\Phploc|\Prophecy\Prophecy\ObjectProphecy $phploc
 */
class PhplocTaskTest extends TestCase {

  protected function setUp() {
    $this->phploc = $this->prophesize(Phploc::class);
  }

  private function createPhplocTask(): PhplocTask {
    $phploc_facade = $this->phploc->reveal();
    return new PhplocTask($phploc_facade);
  }

  /**
   * @dataProvider providerTask
   */
  public function testTask(string $path): void {
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

  public function providerTask(): array {
    return [
      ['/var/www'],
      ['/test/example'],
    ];
  }

}
