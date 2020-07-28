<?php

namespace Acquia\Orca\Command\Report;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Exception\DirectoryNotFoundException;
use Acquia\Orca\Exception\FileNotFoundException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Report\CodeCoverageReportBuilder;
use Acquia\Orca\Utility\StatusTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class ReportCodeCoverageCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'report:code-coverage';

  /**
   * The code coverage report builder.
   *
   * @var \Acquia\Orca\Report\CodeCoverageReportBuilder
   */
  private $reportBuilder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Report\CodeCoverageReportBuilder $report_builder
   *   The code coverage report builder.
   */
  public function __construct(CodeCoverageReportBuilder $report_builder) {
    parent::__construct(self::$defaultName);
    $this->reportBuilder = $report_builder;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['coverage', 'cov'])
      ->setDescription('Displays a code coverage report')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to generate the report from');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $path = $input->getArgument('path');
    try {
      $report = $this->reportBuilder->build($path);
    }
    catch (DirectoryNotFoundException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCode::ERROR;
    }
    catch (FileNotFoundException $e) {
      $output->writeln([
        'Error: No code coverage data available.',
        'Hint: Use the "qa:static-analysis --phploc" command to generate it.',
      ]);
      return StatusCode::ERROR;
    }
    catch (ParseError $e) {
      $output->writeln([
        'Error: Invalid coverage data detected.',
        'Hint: Use the "qa:static-analysis --phploc" command to regenerate it.',
      ]);
      return StatusCode::ERROR;
    }

    (new StatusTable($output))
      ->setRows($report)
      ->render();

    return StatusCode::OK;
  }

}
