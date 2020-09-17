<?php

namespace Acquia\Orca\Options;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Closure;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

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
   * Constructs an instance.
   *
   * @param array $options
   *   The options.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  public function __construct(array $options) {
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
        ])
        // Set required.
        ->setRequired([
          'job',
          'phase',
        ])
        // Set allowed types.
        ->setAllowedTypes('job', 'string')
        ->setAllowedTypes('phase', 'string')
        // Set allowed values.
        ->setAllowedValues('job', $this->isValidJobValue())
        ->setAllowedValues('phase', $this->isValidPhaseValue())
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
  private function isValidJobValue(): Closure {
    return static function ($value): bool {
      try {
        new CiJobEnum($value);
      }
      catch (UnexpectedValueException $e) {
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
  private function isValidPhaseValue(): Closure {
    return static function ($value): bool {
      try {
        new CiJobPhaseEnum($value);
      }
      catch (UnexpectedValueException $e) {
        return FALSE;
      }
      return TRUE;
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

}
