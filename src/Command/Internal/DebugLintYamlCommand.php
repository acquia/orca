<?php

namespace Acquia\Orca\Command\Internal;

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
  protected static $defaultName = 'internal:lint-yaml';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setHidden(TRUE);
  }

}
