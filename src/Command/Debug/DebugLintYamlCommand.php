<?php

namespace Acquia\Orca\Command\Debug;

use Symfony\Component\Yaml\Command\LintCommand;

/**
 * Provides a command.
 */
class DebugLintYamlCommand extends LintCommand {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'debug:lint-yaml';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setHidden(TRUE);
  }

}
