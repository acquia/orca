<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;

/**
 * The loose deprecated code scan CI job.
 */
class LooseDeprecatedCodeScanCiJob extends AbstractCiJob {

  /**
   * The ENV facade.
   *
   * @var \Acquia\Orca\Helper\EnvFacade
   */
  private $envFacade;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\EnvFacade $env_facade
   *   The ENV facade.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(EnvFacade $env_facade, ProcessRunner $process_runner) {
    $this->envFacade = $env_facade;
    $this->processRunner = $process_runner;
  }

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::LOOSE_DEPRECATED_CODE_SCAN();
  }

  /**
   * {@inheritdoc}
   */
  protected function install(CiRunOptions $options): void {
    $this->runOrcaFixtureInit([
      '--force',
      "--sut={$options->getSut()->getPackageName()}",
      '--sut-only',
      '--core=CURRENT_DEV',
      '--no-site-install',
    ], $this->envFacade, $this->processRunner);
  }

  /**
   * {@inheritdoc}
   */
  protected function script(CiRunOptions $options): void {
    $this->processRunner
      ->runOrca(['fixture:status']);

    $sut = $options->getSut();
    $this->processRunner
      ->runOrca([
        'qa:deprecated-code-scan',
        "--sut={$sut->getPackageName()}",
      ]);
  }

}
