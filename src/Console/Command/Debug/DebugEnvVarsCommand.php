<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\Helper\EnvVarsTableBuilder;
use Acquia\Orca\Enum\StatusCodeEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugEnvVarsCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'debug:env-vars';

  /**
   * The environment variables table builder.
   *
   * @var \Acquia\Orca\Console\Command\Debug\Helper\EnvVarsTableBuilder
   */
  private $tableBuilder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Console\Command\Debug\Helper\EnvVarsTableBuilder $table_builder
   *   The environment variables table builder.
   */
  public function __construct(EnvVarsTableBuilder $table_builder) {
    $this->tableBuilder = $table_builder;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['env', 'vars'])
      ->setDescription('Displays ORCA environment variables');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $table = $this->tableBuilder->build($output);
    $table->render();

    return StatusCodeEnum::OK;
  }

}
