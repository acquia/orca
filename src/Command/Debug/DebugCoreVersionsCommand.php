<?php

namespace Acquia\Orca\Command\Debug;

use Acquia\Orca\Command\Fixture\FixtureInitCommand;
use Acquia\Orca\Command\StatusCodes;
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
      $this->getRow(FixtureInitCommand::PREVIOUS_RELEASE),
      $this->getRow(FixtureInitCommand::PREVIOUS_DEV),
      $this->getRow(FixtureInitCommand::CURRENT_RECOMMENDED),
      $this->getRow(FixtureInitCommand::CURRENT_DEV),
      $this->getRow(FixtureInitCommand::NEXT_RELEASE),
      $this->getRow(FixtureInitCommand::NEXT_DEV),
    ];

    (new StatusTable($output))
      ->setRows($overview)
      ->render();
    return StatusCodes::OK;
  }

  /**
   * Gets a table row for a given version constant.
   *
   * @param string $version_constant
   *   The version constant.
   *
   * @return array
   *   A table row.
   */
  private function getRow(string $version_constant): array {
    $row = [$version_constant];
    try {
      switch ($version_constant) {
        case FixtureInitCommand::PREVIOUS_RELEASE:
          $row[] = $this->drupalCoreVersionFinder->getPreviousMinorRelease();
          break;

        case FixtureInitCommand::PREVIOUS_DEV:
          $row[] = $this->drupalCoreVersionFinder->getPreviousDevVersion();
          break;

        case FixtureInitCommand::CURRENT_RECOMMENDED:
          $row[] = $this->drupalCoreVersionFinder->getCurrentRecommendedRelease();
          break;

        case FixtureInitCommand::CURRENT_DEV:
          $row[] = $this->drupalCoreVersionFinder->getCurrentDevVersion();
          break;

        case FixtureInitCommand::NEXT_RELEASE:
          $row[] = $this->drupalCoreVersionFinder->getNextRelease();
          break;

        case FixtureInitCommand::NEXT_DEV:
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
