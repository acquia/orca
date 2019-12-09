<?php

namespace Acquia\Orca\Task\Fixer;

use Acquia\Orca\Enum\PhpcsStandard;
use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Automatically fixes coding standards violations.
 */
class PhpCodeBeautifierAndFixerTask extends TaskBase {

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
   * @param \Acquia\Orca\Enum\PhpcsStandard $standard
   *   The PHPCS standard.
   */
  public function setStandard(PhpcsStandard $standard): void {
    $this->standard = $standard;
  }

}
