<?php

namespace Acquia\Orca\Domain\Tool;

/**
 * Provides an interface for tools.
 */
interface ToolInterface {

  /**
   * Returns the human-readable task label.
   *
   * @return string
   *   The task name, e.g., "Example Task".
   */
  public function label(): string;

  /**
   * Returns a status message describing the tool being run.
   *
   * @return string
   *   A status message, e.g., "Running tool".
   */
  public function statusMessage(): string;

  /**
   * Runs the tool.
   *
   * @param string $path
   *   The path to run the tool on.
   */
  public function run(string $path = ''): void;

}
