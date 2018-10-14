<?php

namespace Acquia\Orca;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs processes.
 *
 * @property \Symfony\Component\Process\ExecutableFinder $executableFinder
 */
trait ProcessRunnerTrait {

  /**
   * Runs a given executable command in a process.
   *
   * @param array $command
   *   An array of command parts, where the first element is an executable name.
   * @param string|null $cwd
   *   The working directory, or NULL to use the working dir of the current PHP
   *   process.
   *
   * @return int
   *   The exit status code.
   */
  public function runExecutableProcess(array $command, ?string $cwd = NULL): int {
    $command[0] = (new ExecutableFinder())->find($command[0]);

    if (is_null($command[0])) {
      throw new RuntimeException(sprintf('Could not find executable: %s.', $command[0]));
    }

    return $this->runProcess($command, $cwd);
  }

  /**
   * Runs a given command in a process.
   *
   * @param array $command
   *   An array of command parts.
   * @param string|null $cwd
   *   The working directory, or NULL to use the working dir of the current PHP
   *   process.
   *
   * @return int
   *   The exit status code.
   */
  public function runProcess(array $command, ?string $cwd = NULL): int {
    if ($this->hasIo()) {
      $this->io()->comment(sprintf('Executing "%s"', implode(' ', $command)));
    }

    $status = (new Process($command, $cwd))
      ->setTimeout(0)->run(function () {
        $buffer = func_get_arg(1);
        echo $buffer;
      });

    if ($this->hasIo()) {
      $this->io()->newLine();
    }

    return $status;
  }

  /**
   * Determines whether or not the current object has IO.
   *
   * @return bool
   */
  private function hasIo(): bool {
    return method_exists($this, 'io');
  }

}
