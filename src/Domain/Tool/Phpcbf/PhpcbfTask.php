<?php

namespace Acquia\Orca\Domain\Tool\Phpcbf;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Exception\TaskFailureException;
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
  private $standard = PhpcsStandardEnum::DEFAULT;

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
    $this->phpcsConfigurator->prepareTemporaryConfig(new PhpcsStandardEnum($this->standard));
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
   * @param \Acquia\Orca\Enum\PhpcsStandardEnum $standard
   *   The PHPCS standard.
   */
  public function setStandard(PhpcsStandardEnum $standard): void {
    $this->standard = $standard;
  }

}
