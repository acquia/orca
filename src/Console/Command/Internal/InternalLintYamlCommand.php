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
   * The default command name.
   *
   * @var string
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
