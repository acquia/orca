<?php

namespace Acquia\Orca\Domain\Server;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Provides callback to output Process data.
 */
class ProcessOutputCallback {

  /**
   * The output variable.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output variable.
   */
  public function __construct(SymfonyStyle $output) {
    $this->output = $output;
  }

  /**
   * Callback function to output data from process.
   */
  public function __invoke(Process $process) {
    $this->output->comment("Running Command : " . $process->getCommandLine());
  }

}
