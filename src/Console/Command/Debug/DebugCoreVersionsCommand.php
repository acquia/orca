<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Console\Helper\StatusTable;
use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugCoreVersionsCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'debug:core-versions';

  /**
   * The Drupal core version finder.
   *
   * @var \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionFinder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder $drupal_core_version_finder
   *   The Drupal core version finder.
   */
  public function __construct(DrupalCoreVersionFinder $drupal_core_version_finder) {
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['core'])
      ->setDescription('Provides an overview of Drupal Core versions');
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Getting version data via Composer. This takes a while.');

    $overview = [];
    foreach (DrupalCoreVersionEnum::values() as $version) {
      $overview[] = [
        $version,
        $this->drupalCoreVersionFinder->getPretty(new DrupalCoreVersionEnum($version)),
      ];
    }

    (new StatusTable($output))
      ->setRows($overview)
      ->render();
    return StatusCodeEnum::OK;
  }

}
