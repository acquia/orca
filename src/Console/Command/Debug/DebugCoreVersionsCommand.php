<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\Helper\CoreVersionsTableBuilder;
use Acquia\Orca\Enum\StatusCodeEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugCoreVersionsCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'debug:core-versions';

  /**
   * The core versions table builder.
   *
   * @var \Acquia\Orca\Console\Command\Debug\Helper\CoreVersionsTableBuilder
   */
  private $coreVersionsTableBuilder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Console\Command\Debug\Helper\CoreVersionsTableBuilder $core_versions_table_builder
   *   The core versions table builder.
   */
  public function __construct(CoreVersionsTableBuilder $core_versions_table_builder) {
    $this->coreVersionsTableBuilder = $core_versions_table_builder;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['core'])
      ->setDescription('Provides an overview of Drupal Core versions')
      ->addOption('examples', NULL, InputOption::VALUE_NONE, 'Include example version strings')
      ->addOption('resolve', NULL, InputOption::VALUE_NONE, 'Include the exact versions Composer would actually install. Makes HTTP requests and increases execution time');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if ($input->getOption('resolve')) {
      $output->writeln('Getting version data via Composer. This can take a while.');
    }

    $include_examples = $input->getOption('examples');
    $include_resolved = $input->getOption('resolve');
    $table = $this->coreVersionsTableBuilder->build($output, $include_examples, $include_resolved);
    $table->render();

    return StatusCodeEnum::OK;
  }

}
