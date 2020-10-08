<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
   * The Drupal core version resolver.
   *
   * @var \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionResolver;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version resolver.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_resolver) {
    $this->drupalCoreVersionResolver = $drupal_core_version_resolver;
    parent::__construct();
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
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Getting version data via Composer. This can take a while.');

    (new Table($output))
      ->setHeaders(['Version', 'Resolved', 'Description'])
      ->setRows($this->getRows())
      ->render();
    return StatusCodeEnum::OK;
  }

  /**
   * Gets the table rows.
   *
   * @return array
   *   An array of row data.
   */
  private function getRows(): array {
    $overview = [];
    foreach (DrupalCoreVersionEnum::values() as $version) {
      try {
        $value = $this->drupalCoreVersionResolver->resolve($version);
      }
      catch (OrcaVersionNotFoundException $e) {
        $value = '~';
      }
      $overview[] = [$version->getKey(), $value, $version->getDescription()];
    }
    return $overview;
  }

}
