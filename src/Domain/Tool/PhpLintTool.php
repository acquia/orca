<?php

namespace Acquia\Orca\Domain\Tool;

use Acquia\Orca\Exception\OrcaTaskFailureException;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs PHP Lint.
 */
class PhpLintTool implements ToolInterface {

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ProcessRunner $process_runner) {
    $this->processRunner = $process_runner;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHP Lint';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Linting PHP files';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Acquia\Orca\Exception\OrcaTaskFailureException
   */
  public function run(string $path = ''): void {
    try {
      $this->processRunner->runOrcaVendorBin([
        'parallel-lint',
        '-e',
        'inc,install,module,php,profile,test,theme',
        '--exclude',
        'vendor',
        '--colors',
        '--blame',
        '.',
      ], $path);
    }
    catch (ProcessFailedException $e) {
      // Parallel lint exits with a 254 status code if it doesn't find any PHP
      // files to lint, which should not be considered a failure.
      if ($e->getProcess()->getExitCode() === 254) {
        return;
      }
      throw new OrcaTaskFailureException($e->getMessage());
    }
  }

}
