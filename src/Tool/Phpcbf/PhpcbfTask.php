<?php

namespace Acquia\Orca\Tool\Phpcbf;

use Acquia\Orca\Helper\Exception\TaskFailureException;
use Acquia\Orca\Tool\Helper\PhpcsStandard;
use Acquia\Orca\Tool\TaskBase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Automatically fixes coding standards violations.
 */
class PhpcbfTask extends TaskBase {

  /**
   * The standard to use.
   *
   * @var string
   */
  private $standard = PhpcsStandard::DEFAULT;

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
   */
  public function execute(): void {
    $this->phpcsConfigurator->prepareTemporaryConfig(new PhpcsStandard($this->standard));
    try {
      $this->processRunner->runOrcaVendorBin([
        'phpcbf',
        realpath($this->getPath()),
      ], $this->phpcsConfigurator->getTempDir());
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
    finally {
      $this->phpcsConfigurator->cleanupTemporaryConfig();
    }
  }

  /**
   * Sets the standard to use.
   *
   * @param \Acquia\Orca\Tool\Helper\PhpcsStandard $standard
   *   The PHPCS standard.
   */
  public function setStandard(PhpcsStandard $standard): void {
    $this->standard = $standard;
  }

}
