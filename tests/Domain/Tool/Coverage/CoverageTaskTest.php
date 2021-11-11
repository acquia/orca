<?php

namespace Acquia\Orca\Tests\Domain\Tool\Coverage;

use Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder;
use Acquia\Orca\Domain\Tool\Coverage\CoverageTask;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder|\Prophecy\Prophecy\ObjectProphecy $builder
 * @property \Symfony\Component\Console\Output\OutputInterface|\Prophecy\Prophecy\ObjectProphecy $symfonyOutput
 * @coversDefaultClass \Acquia\Orca\Domain\Tool\Coverage\CoverageTask
 */
class CoverageTaskTest extends TestCase {

  protected function setUp(): void {
    $this->builder = $this->prophesize(CodeCoverageReportBuilder::class);
    $this->symfonyOutput = $this->prophesize(OutputInterface::class);
  }

  protected function createTask(): CoverageTask {
    $coverage_report_builder = $this->builder->reveal();
    $output = $this->symfonyOutput->reveal();
    return new CoverageTask($coverage_report_builder, $output);
  }

  /**
   * @dataProvider providerTask
   */
  public function testTask(string $path): void {
    $this->builder
      ->build($path)
      ->shouldBeCalledOnce();
    $task = $this->createTask();

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

  public function testNoFilesToScan(): void {
    $display = 'Example display.';
    $this->builder
      ->build(Argument::any())
      ->shouldBeCalledOnce()
      ->willThrow(new OrcaFileNotFoundException($display));
    $this->symfonyOutput
      ->writeln($display . PHP_EOL)
      ->shouldBeCalledOnce();
    $task = $this->createTask();

    $task->execute();
  }

}
