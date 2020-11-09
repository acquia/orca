<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;

/**
 * The static code analysis CI job.
 */
class StaticCodeAnalysisCiJob extends AbstractCiJob {

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
  protected function jobName(): CiJobEnum {
    return CiJobEnum::STATIC_CODE_ANALYSIS();
  }

  /**
   * {@inheritdoc}
   */
  protected function script(CiRunOptions $options): void {
    $sut = $options->getSut();
    $this->processRunner
      ->runOrca([
        'qa:static-analysis',
        $sut->getRepositoryUrlAbsolute(),
      ]);
  }

}
