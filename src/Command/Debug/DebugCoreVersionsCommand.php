<?php

namespace Acquia\Orca\Command\Debug;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Enum\DrupalCoreVersion;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Acquia\Orca\Utility\StatusTable;
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
   * @var \Acquia\Orca\Utility\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionFinder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\DrupalCoreVersionFinder $drupal_core_version_finder
   *   The Drupal core version finder.
   */
  public function __construct(DrupalCoreVersionFinder $drupal_core_version_finder) {
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['core'])
      ->setDescription('Provides an overview of Drupal Core versions');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Getting version data via Composer. This takes a while.');

    $overview = [
      $this->getRow(DrupalCoreVersion::PREVIOUS_RELEASE),
      $this->getRow(DrupalCoreVersion::PREVIOUS_DEV),
      $this->getRow(DrupalCoreVersion::CURRENT_RECOMMENDED),
      $this->getRow(DrupalCoreVersion::CURRENT_DEV),
      $this->getRow(DrupalCoreVersion::NEXT_RELEASE),
      $this->getRow(DrupalCoreVersion::NEXT_DEV),
    ];

    (new StatusTable($output))
      ->setRows($overview)
      ->render();
    return StatusCodes::OK;
  }

  /**
   * Gets a table row for a given version constant.
   *
   * @param string $core_version
   *   The version constant.
   *
   * @return array
   *   A table row.
   */
  private function getRow(string $core_version): array {
    $row = [$core_version];
    try {
      switch ($core_version) {
        case DrupalCoreVersion::PREVIOUS_RELEASE:
          $row[] = $this->drupalCoreVersionFinder->getPreviousMinorRelease();
          break;

        case DrupalCoreVersion::PREVIOUS_DEV:
          $row[] = $this->drupalCoreVersionFinder->getPreviousDevVersion();
          break;

        case DrupalCoreVersion::CURRENT_RECOMMENDED:
          $row[] = $this->drupalCoreVersionFinder->getCurrentRecommendedRelease();
          break;

        case DrupalCoreVersion::CURRENT_DEV:
          $row[] = $this->drupalCoreVersionFinder->getCurrentDevVersion();
          break;

        case DrupalCoreVersion::NEXT_RELEASE:
          $row[] = $this->drupalCoreVersionFinder->getNextRelease();
          break;

        case DrupalCoreVersion::NEXT_DEV:
          $row[] = $this->drupalCoreVersionFinder->getNextDevVersion();
          break;
      }
    }
    catch (\RuntimeException $e) {
      $row[] = '~';
    }
    return $row;
  }

}
