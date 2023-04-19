<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Helper\Filesystem\FinderFactory;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Customizes fixtures.
 */
class FixtureCustomizer {

  /**
   * The Finder Factory.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FinderFactory
   */
  private $finderFactory;

  /**
   * The Filesystem service.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The path handler service for the fixture.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixturePathHandler;

  /**
   * The output variable.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FinderFactory $finderFactory
   *   The finder factory.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem service.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixturePathHandler
   *   The path handler for fixture.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output variable.
   */
  public function __construct(
      FinderFactory $finderFactory,
      Filesystem $filesystem,
      FixturePathHandler $fixturePathHandler,
      OutputInterface $output
  ) {
      $this->finderFactory = $finderFactory;
      $this->filesystem = $filesystem;
      $this->fixturePathHandler = $fixturePathHandler;
      $this->output = $output;
  }

  /**
   * Runs all customisations.
   */
  public function runCustomizations(FixtureOptions $options): void {
    $this->perzParagraphsRemoval($options);
  }

  /**
   * Removes paragraph module tests from drupal/acquia_perz.
   *
   * The package drupal/acquia_perz requires the paragraphs module for running
   * its phpunit tests. But paragraph module is only a dev-dependency of
   * drupal/acquia_perz and thus does not get included in the fixture unless
   * drupal/acquia-perz is the SUT. So we are removing all tests requiring
   * paragraphs module when drupal/acquia_perz is not the SUT.
   */
  public function perzParagraphsRemoval(FixtureOptions $options): void {

    $this->output->writeln('Performing drupal/acquia_perz related customisations.');

    if (!is_null($options->getSut()) && $options->getSut()->getPackageName() === 'drupal/acquia_perz') {
      $this->output->writeln('No customisations required for drupal/acquia_perz
       as it is the SUT.');
      return;
    }

    $finder = $this->finderFactory->create();

    try {
      $paragraph_files = $finder->in($this->fixturePathHandler
        ->getPath('docroot/modules/contrib/acquia_perz'))
        ->contains('Drupal\Tests\paragraphs');

      if (iterator_count($paragraph_files) === 0) {
        $this->output->writeln('No customisations required for drupal/acquia_perz
         since no files found for removal.');
        return;
      }

      foreach ($paragraph_files as $file) {
        $this->output->writeln("Removing $file");
      }

      $this->filesystem->remove($paragraph_files);

      $this->output->writeln('Files removed successfully.');
    }
    catch (\Exception $e) {
      $this->output->writeln("Customisation unsuccessful. \n" . $e->getMessage());
    }

  }

}
