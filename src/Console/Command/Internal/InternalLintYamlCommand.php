<?php

namespace Acquia\Orca\Console\Command\Internal;

use Symfony\Component\Yaml\Command\LintCommand;

/**
 * Provides a command.
 *
 * @codeCoverageIgnore
 */
class InternalLintYamlCommand extends LintCommand {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'internal:lint-yaml';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    parent::configure();
    $this->setHidden(TRUE);
  }

}
