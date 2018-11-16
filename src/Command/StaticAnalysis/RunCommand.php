<?php

namespace Acquia\Orca\Command\StaticAnalysis;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\StaticAnalysisRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a command.
 *
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Acquia\Orca\StaticAnalysisRunner $staticAnalysisRunner
 */
class RunCommand extends Command {

  protected static $defaultName = 'static-analysis:run';

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\StaticAnalysisRunner $static_analysis_runner
   *   The static analysis tools runner.
   */
  public function __construct(Filesystem $filesystem, StaticAnalysisRunner $static_analysis_runner) {
    $this->filesystem = $filesystem;
    $this->staticAnalysisRunner = $static_analysis_runner;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['analyze'])
      ->setDescription('Runs static analysis tools')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to analyze.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $path = $input->getArgument('path');
    if (!$this->filesystem->exists($path)) {
      $output->writeln(sprintf('Error: No such path: %s.', $path));
      return StatusCodes::ERROR;
    }
    return $this->staticAnalysisRunner->run($path);
  }

}
