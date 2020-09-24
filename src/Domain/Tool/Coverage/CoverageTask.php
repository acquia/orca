<?php

namespace Acquia\Orca\Domain\Tool\Coverage;

use Acquia\Orca\Console\Helper\StatusTable;
use Acquia\Orca\Domain\Tool\TaskInterface;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays a code coverage report.
 */
class CoverageTask implements TaskInterface {

  /**
   * The coverage report builder.
   *
   * @var \Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder
   */
  private $builder;

  /**
   * A filesystem path.
   *
   * @var string
   */
  private $path = '';

  /**
   * The output interface.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder $coverage_report_builder
   *   The code coverage report builder.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   */
  public function __construct(CodeCoverageReportBuilder $coverage_report_builder, OutputInterface $output) {
    $this->builder = $coverage_report_builder;
    $this->output = $output;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $rows = $this->builder->build($this->path);
      (new StatusTable($this->output))
        ->setRows($rows)
        ->render();
    }
    catch (OrcaFileNotFoundException $e) {
      $this->output->writeln($e->getMessage() . PHP_EOL);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function setPath(string $path): TaskInterface {
    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Code Coverage';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Estimating Code Coverage';
  }

}
