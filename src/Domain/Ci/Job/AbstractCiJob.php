<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Fixture\FixtureCreator;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides an abstract class for CI Job classes.
 */
abstract class AbstractCiJob {

  /**
   * Gets the job name.
   *
   * @return \Acquia\Orca\Enum\CiJobEnum
   *   The job name.
   */
  final public function getJobName(): CiJobEnum {
    return $this->jobName();
  }

  /**
   * Gets the Drupal core version.
   *
   * @return \Acquia\Orca\Enum\DrupalCoreVersionEnum|null
   *   The Drupal core version if specified or NULL if not.
   */
  final public function getDrupalCoreVersion(): ?DrupalCoreVersionEnum {
    return $this->getJobName()->getDrupalCoreVersion();
  }

  /**
   * Declares the job name.
   *
   * @return \Acquia\Orca\Enum\CiJobEnum
   *   The job enum.
   */
  abstract protected function jobName(): CiJobEnum;

  /**
   * Runs a given job.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  final public function run(CiRunOptions $options): void {
    if ($this->exitEarly()) {
      return;
    }

    switch ($options->getPhase()->getValue()) {
      case CiJobPhaseEnum::BEFORE_INSTALL:
        $this->beforeInstall($options);
        break;

      case CiJobPhaseEnum::INSTALL:
        $this->install($options);
        break;

      case CiJobPhaseEnum::BEFORE_SCRIPT:
        $this->beforeScript($options);
        break;

      case CiJobPhaseEnum::SCRIPT:
        $this->script($options);
        break;

      case CiJobPhaseEnum::BEFORE_CACHE:
        $this->beforeCache($options);
        break;

      case CiJobPhaseEnum::AFTER_SUCCESS:
        $this->afterSuccess($options);
        break;

      case CiJobPhaseEnum::AFTER_FAILURE:
        $this->afterFailure($options);
        break;

      case CiJobPhaseEnum::BEFORE_DEPLOY:
        $this->beforeDeploy($options);
        break;

      case CiJobPhaseEnum::DEPLOY:
        $this->deploy($options);
        break;

      case CiJobPhaseEnum::AFTER_DEPLOY:
        $this->afterDeploy($options);
        break;

      case CiJobPhaseEnum::AFTER_SCRIPT:
        $this->afterScript($options);

    }
  }

  /**
   * Determines whether or not to exit the job early.
   */
  protected function exitEarly(): bool {
    return FALSE;
  }

  /**
   * Runs before the install stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeInstall(CiRunOptions $options): void {}

  /**
   * Runs at the install stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function install(CiRunOptions $options): void {}

  /**
   * Runs before the script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeScript(CiRunOptions $options): void {}

  /**
   * Runs at the script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function script(CiRunOptions $options): void {}

  /**
   * Runs before storing a build cache.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeCache(CiRunOptions $options): void {}

  /**
   * Runs after a successful script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterSuccess(CiRunOptions $options): void {}

  /**
   * Runs after a failing script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterFailure(CiRunOptions $options): void {}

  /**
   * Runs before the deploy stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeDeploy(CiRunOptions $options): void {}

  /**
   * Runs at the deploy stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function deploy(CiRunOptions $options): void {}

  /**
   * Runs after the deploy stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterDeploy(CiRunOptions $options): void {}

  /**
   * Runs as the last stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterScript(CiRunOptions $options): void {}

  /**
   * Determines whether or not the job is redundant.
   *
   * @param \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker $redundant_job_checker
   *   The redundant job checker.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   *   TRUE if the job is redundant or FALSE if not.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  protected function isRedundant(RedundantJobChecker $redundant_job_checker, OutputInterface $output): bool {
    if ($redundant_job_checker->isRedundant($this->jobName())) {
      $output->writeln('This job is currently redundant given the spread of available Drupal core versions. Exiting.');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determines whether a matching Drupal core version exists or not.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version resolver.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   *   TRUE if a matching version exists or FALSE if not.
   */
  protected function matchingCoreVersionExists(DrupalCoreVersionResolver $drupal_core_version_resolver, OutputInterface $output): bool {
    $version = $this->getDrupalCoreVersion();
    if (!$version) {
      throw new \LogicException("Can't test for a matching Drupal core version with a job that doesn't specify one.");
    }
    try {
      $drupal_core_version_resolver->resolvePredefined($version);
    }
    catch (OrcaVersionNotFoundException $e) {
      $output->writeln('There is currently no available version of Drupal core corresponding to this job. Exiting.');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Runs a fixture:init ORCA command.
   *
   * Handles conditional "--profile" and "--project-template" options.
   *
   * @param array $command
   *   An array of command arguments.
   * @param \Acquia\Orca\Helper\EnvFacade $env_facade
   *   The ENV facade.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  protected function runOrcaFixtureInit(array $command, EnvFacade $env_facade, ProcessRunner $process_runner): void {
    array_unshift($command, 'fixture:init');

    $profile = $env_facade->get('ORCA_FIXTURE_PROFILE');
    if ($profile) {
      $command[] = "--profile={$profile}";
    }

    $project_template = $env_facade->get('ORCA_FIXTURE_PROJECT_TEMPLATE');
    if ($project_template) {
      $command[] = "--project-template={$project_template}";
    }

    $process_runner->runOrca($command);
  }

  /**
   * Runs a qa:automated-tests ORCA command.
   *
   * Handles conditional "--profile" options.
   *
   * @param array $command
   *   An array of command arguments.
   * @param \Acquia\Orca\Helper\EnvFacade $env_facade
   *   The ENV facade.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  protected function runOrcaQaAutomatedTests(array $command, EnvFacade $env_facade, ProcessRunner $process_runner): void {
    array_unshift($command, 'qa:automated-tests');

    $already_sut_only = in_array('--sut-only', $command, TRUE);
    $profile = $env_facade->get('ORCA_FIXTURE_PROFILE') ?: FixtureCreator::DEFAULT_PROFILE;
    if (!$already_sut_only && $profile !== FixtureCreator::DEFAULT_PROFILE) {
      $command[] = '--sut-only';
    }

    $process_runner->runOrca($command);
  }

}
