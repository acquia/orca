<?php

namespace Acquia\Orca\Command\Lint;

use Symfony\Component\Yaml\Command\LintCommand;

/**
 * Provides a command.
 */
class LintYamlCommand extends LintCommand {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'lint:yaml';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setHidden(TRUE);
  }

}
