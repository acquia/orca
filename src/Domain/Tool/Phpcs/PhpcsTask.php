<?php

namespace Acquia\Orca\Domain\Tool\Phpcs;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Exception\TaskFailureException;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Sniffs for coding standards violations.
 */
class PhpcsTask extends TaskBase {

  public const JSON_LOG_PATH = 'var/log/phpcs.json';

  /**
   * The standard to use.
   *
   * @var string
   */
  private $standard = PhpcsStandardEnum::DEFAULT;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHP Code Sniffer (PHPCS)';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Sniffing for coding standards violations';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->phpcsConfigurator->prepareTemporaryConfig(new PhpcsStandardEnum($this->standard));

    try {
      $this->runPhpcsCommand();
    }
    catch (ProcessFailedException $e) {
      // Swallow failure from the first run so as not to prevent the log run,
      // which will fail identically, ensuring the correct exception is thrown.
    }

    try {
      $this->logResults();
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException(NULL, NULL, $e);
    }
    finally {
      $this->phpcsConfigurator->cleanupTemporaryConfig();
    }
  }

  /**
   * Sets the standard to use.
   *
   * @param \Acquia\Orca\Enum\PhpcsStandardEnum $standard
   *   The PHPCS standard.
   */
  public function setStandard(PhpcsStandardEnum $standard): void {
    $this->standard = $standard;
  }

  /**
   * Runs and logs the output to a file.
   */
  private function logResults(): void {
    $this->output->comment('Logging results...');
    $this->runPhpcsCommand([
      "--report-file={$this->orca->getPath( self::JSON_LOG_PATH)}",
    ]);
  }

  /**
   * Runs the PHPCS command.
   *
   * @param array $options
   *   Command line options to add to the defaults.
   */
  private function runPhpcsCommand(array $options = []): void {
    $command = array_merge([
      'phpcs',
      '-s',
    ], $options, [
      realpath($this->getPath()),
    ]);
    $this->processRunner->runOrcaVendorBin($command, $this->phpcsConfigurator->getTempDir());
  }

}
