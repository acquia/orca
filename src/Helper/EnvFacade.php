<?php

namespace Acquia\Orca\Helper;

/**
 * Provides a facade for environment variables.
 *
 * The sole purpose of this class is to make \Env an injectable dependency.
 */
class EnvFacade {

  /**
   * Gets the value of a given environment variable.
   *
   * @param string $variable
   *   The variable name.
   * @param mixed|null $default
   *   The default return value.
   *
   * @return mixed
   *   The value.
   */
  public function get(string $variable, $default = NULL) {
    $value = $this->getVar($variable);

    if (is_null($value)) {
      return $default;
    }

    return $value;
  }

  /**
   * Gets the value of a given environment variable.
   *
   * This method is extracted exclusively for testability.
   *
   * @param string $variable
   *   The variable name.
   *
   * @return mixed
   *   The value.
   *
   * @codeCoverageIgnore
   */
  protected function getVar($variable) {
    return \Env::get($variable);
  }

}
