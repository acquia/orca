<?php

namespace Acquia\Orca\Helper;

use Env;

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
   *
   * @return mixed
   *   The value.
   *
   * @codeCoverageIgnore
   */
  public function get(string $variable) {
    return Env::get($variable);
  }

}
