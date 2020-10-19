<?php

namespace Acquia\Orca\Domain\Tool;

use Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator;
use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs PHPCBF.
 */
class PhpcbfTool implements ToolInterface {

  /**
   * The PHPCS configurator.
   *
   * @var \Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator
   */
  private $phpcsConfigurator;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * The standard to use.
   *
   * @var string
   */
  private $standard = PhpcsStandardEnum::DEFAULT;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator $phpcs_configurator
   *   The PHPCS configurator.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(PhpcsConfigurator $phpcs_configurator, ProcessRunner $process_runner) {
    $this->phpcsConfigurator = $phpcs_configurator;
    $this->processRunner = $process_runner;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHP Code Beautifier and Fixer (PHPCBF)';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Fixing coding standards violations';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Acquia\Orca\Exception\OrcaTaskFailureException
   */
  public function run(string $path = ''): void {
    try {
      $this->phpcsConfigurator->prepareTemporaryConfig(new PhpcsStandardEnum($this->standard));
      $this->processRunner->runOrcaVendorBin([
        'phpcbf',
        $path,
      ], $this->phpcsConfigurator->getTempDir());
    }
    catch (ProcessFailedException $e) {
      throw new OrcaTaskFailureException($e->getMessage());
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

}
