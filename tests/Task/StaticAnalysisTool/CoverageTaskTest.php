<?php

namespace Acquia\Orca\Tests\Task\StaticAnalysisTool;

use Acquia\Orca\Exception\FileNotFoundException;
use Acquia\Orca\Task\CodeCoverageReportBuilder;
use Acquia\Orca\Task\StaticAnalysisTool\CoverageTask;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Task\CodeCoverageReportBuilder|\Prophecy\Prophecy\ObjectProphecy $builder
 * @property \Symfony\Component\Console\Output\OutputInterface|\Prophecy\Prophecy\ObjectProphecy $symfonyOutput
 * @coversDefaultClass \Acquia\Orca\Task\StaticAnalysisTool\CoverageTask
 */
class CoverageTaskTest extends TestCase {

  protected function setUp() {
    $this->builder = $this->prophesize(CodeCoverageReportBuilder::class);
    $this->symfonyOutput = $this->prophesize(OutputInterface::class);
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
      ->willThrow(new FileNotFoundException($display));
    $this->symfonyOutput
      ->writeln($display . PHP_EOL)
      ->shouldBeCalledOnce();
    $task = $this->createTask();

    $task->execute();
  }

  protected function createTask(): CoverageTask {
    $coverage_report_builder = $this->builder->reveal();
    $output = $this->symfonyOutput->reveal();
    return new CoverageTask($coverage_report_builder, $output);
  }

}
