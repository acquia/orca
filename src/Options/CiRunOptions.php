<?php

namespace Acquia\Orca\Options;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Encapsulates CI run options.
 */
class CiRunOptions {

  /**
   * The resolved options.
   *
   * @var array
   */
  private $options;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param array $options
   *   The options.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  public function __construct(PackageManager $package_manager, array $options) {
    $this->packageManager = $package_manager;
    $this->resolve($options);
  }

  /**
   * Resolves the given options.
   *
   * @param array $options
   *   The options to resolve.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  private function resolve(array $options): void {
    try {
      $this->options = (new OptionsResolver())
        // Set defined.
        ->setDefined([
          'job',
          'phase',
          'sut',
        ])
        // Set required.
        ->setRequired([
          'job',
          'phase',
          'sut',
        ])
        // Set allowed types.
        ->setAllowedTypes('job', 'string')
        ->setAllowedTypes('phase', 'string')
        ->setAllowedTypes('sut', 'string')
        // Set allowed values.
        ->setAllowedValues('job', $this->isValidJobValue())
        ->setAllowedValues('phase', $this->isValidPhaseValue())
        ->setAllowedValues('sut', $this->isValidSutValue())
        // Resolve.
        ->resolve($options);
    }
    catch (UndefinedOptionsException | InvalidOptionsException $e) {
      throw new OrcaInvalidArgumentException($e->getMessage());
    }
  }

  /**
   * Validates the "job" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidJobValue(): \Closure {
    return static function ($value): bool {
      try {
        /* @phan-suppress-next-line PhanNoopNew */
        new CiJobEnum($value);
      }
      catch (\UnexpectedValueException $e) {
        return FALSE;
      }
      return TRUE;
    };
  }

  /**
   * Validates the "job" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidPhaseValue(): \Closure {
    return static function ($value): bool {
      try {
        /* @phan-suppress-next-line PhanNoopNew */
        new CiJobPhaseEnum($value);
      }
      catch (\UnexpectedValueException $e) {
        return FALSE;
      }
      return TRUE;
    };
  }

  /**
   * Validates the "sut" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidSutValue(): \Closure {
    return function ($value): bool {
      return $this->packageManager->exists($value);
    };
  }

  /**
   * Gets the job enum.
   *
   * @return \Acquia\Orca\Enum\CiJobEnum
   *   The job enum.
   */
  public function getJob(): CiJobEnum {
    return new CiJobEnum($this->options['job']);
  }

  /**
   * Gets the phase enum.
   *
   * @return \Acquia\Orca\Enum\CiJobPhaseEnum
   *   The phase enum.
   */
  public function getPhase(): CiJobPhaseEnum {
    return new CiJobPhaseEnum($this->options['phase']);
  }

  /**
   * Gets the SUT.
   *
   * @return \Acquia\Orca\Domain\Package\Package
   *   The SUT package.
   */
  public function getSut(): Package {
    return $this->packageManager->get($this->options['sut']);
  }

}
