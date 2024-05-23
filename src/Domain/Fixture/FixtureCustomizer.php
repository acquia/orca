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
    OutputInterface $output,
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
    $this->removePerzParagraphsTests($options);
    $this->removeAcquiaDamCkeditorTests($options);
    $this->modifyDrupalKernel($options);
    $this->modifyPhpunitConfig($options);
  }

  /**
   * Removes paragraph module tests from drupal/acquia_perz.
   *
   * The package drupal/acquia_perz requires the paragraphs module for running
   * its phpunit tests as it depends on classes only present in the paragraph
   * module. But paragraphs module is only a dev-dependency of
   * drupal/acquia_perz and thus does not get included in the fixture unless
   * drupal/acquia-perz is the SUT. This causes everyone else's builds to fail.
   * So we are removing all tests requiring paragraphs module when
   * drupal/acquia_perz is not the SUT.
   */
  public function removePerzParagraphsTests(FixtureOptions $options): void {
    $this->output->writeln("\nPerforming drupal/acquia_perz related customisations.\n");

    if (!is_null($options->getSut()) && $options->getSut()
      ->getPackageName() === 'drupal/acquia_perz') {
      $this->output->writeln('No customizations required for drupal/acquia_perz as it is the SUT.');
      return;
    }

    $this->removeModuleDevDependencyTests($options, 'drupal/acquia_perz', 'Drupal\Tests\paragraphs');
  }

  /**
   * Removes ckeditor module tests from drupal/acquia_dam.
   *
   * The package drupal/acquia_dam requires the ckeditor module for running
   * its phpunit tests as it depends on classes only present in the ckeditor
   * module. But ckeditor module is only a dev-dependency of
   * drupal/acquia_dam and thus does not get included in the fixture unless
   * drupal/acquia_dam is the SUT. This causes everyone else's builds to fail.
   * So we are removing all tests requiring paragraphs module when
   * drupal/acquia_dam is not the SUT.
   */
  public function removeAcquiaDamCkeditorTests(FixtureOptions $options): void {
    $this->output->writeln("\nPerforming drupal/acquia_dam related customisations.\n");

    if (!is_null($options->getSut()) && $options->getSut()
      ->getPackageName() === 'drupal/acquia_dam') {
      $this->output->writeln("\nNo customizations required for drupal/acquia_dam as it is the SUT.\n");
      return;
    }

    $this->removeModuleDevDependencyTests($options, 'drupal/acquia_dam', 'Drupal\Tests\ckeditor');
  }

  /**
   * Remove dev-dependency tests from modules.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   * @param string $module_name
   *   The name of the module whose dev-dependency tests to be removed.
   * @param string $search_string
   *   The tests to be searched for.
   */
  protected function removeModuleDevDependencyTests(
    FixtureOptions $options,
    string $module_name,
    string $search_string,
  ): void {
    $finder = $this->finderFactory->create();
    // Converting drupal/acquia_dam to acquia_dam.
    $module_name = explode("/", $module_name)[1];

    try {
      $files = $finder->in($this->fixturePathHandler
        ->getPath('docroot/modules/contrib/' . $module_name))
        ->contains($search_string);

      if (iterator_count($files) === 0) {
        $this->output->writeln("\nNo customizations required since no files found for removal.\n");
        return;
      }

      foreach ($files as $file) {
        $this->output->writeln("Removing $file");
      }

      $this->filesystem->remove($files);

      $this->output->writeln("\nFiles removed successfully.\n\n");
    }
    catch (\Exception $e) {
      $this->output->writeln("Customisation unsuccessful. \n" . $e->getMessage());
    }
  }

  /**
   * Modifies DrupalKernel.php to fix Drupal 9 warnings in PHP 8.2 and above.
   */
  public function modifyDrupalKernel(FixtureOptions $options): void {
    if (version_compare(PHP_VERSION, '8.2') < 0 || !$options->coreVersionParsedMatches('^9')) {
      return;
    }

    $drupal_kernel_path = 'docroot/core/lib/Drupal/Core/DrupalKernel.php';

    if (!$this->fixturePathHandler->exists($drupal_kernel_path)) {
      return;
    }

    $this->output->writeln('Suppressing Drupal 9 deprecation notices for functional tests.');

    $path = $this->fixturePathHandler
      ->getPath($drupal_kernel_path);

    $target = 'error_reporting(E_STRICT | E_ALL)';

    $change = 'error_reporting(E_ALL & ~E_DEPRECATED)';

    $this->replaceStringInFile($target, $change, $path);

  }

  /**
   * Modify phpunit.xml.dist to fix D9 phpunit warnings in PHP 8.2 and above.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   */
  public function modifyPhpunitConfig(FixtureOptions $options): void {
    if (version_compare(PHP_VERSION, '8.2') < 0 || !$options->coreVersionParsedMatches('^9')) {
      return;
    }

    $phpunit_xml_path = 'docroot/core/phpunit.xml.dist';

    if (!$this->fixturePathHandler->exists($phpunit_xml_path)) {
      return;
    }

    $this->output->writeln('Suppressing Drupal 9 deprecation notices for phpunit tests.');

    $path = $this->fixturePathHandler
      ->getPath($phpunit_xml_path);

    // Change error_reporting value to E_ALL & ~E_DEPRECATED.
    $target = '<ini name="error_reporting" value="32767"/>';

    $change = '<ini name="error_reporting" value="24575"/>';

    $this->replaceStringInFile($target, $change, $path);

  }

  /**
   * Replaces one string with another in a file whose path is provided.
   *
   * @param string $old
   *   The string to be replaced.
   * @param string $new
   *   The new string.
   * @param string $path
   *   The file path.
   */
  public function replaceStringInFile(string $old, string $new, string $path): void {
    $str = file_get_contents($path);

    $str = str_replace($old, $new, $str);

    file_put_contents($path, $str);
  }

}
