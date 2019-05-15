<?php

namespace Acquia\Orca\Task\DeprecatedCodeScanner;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Scans for deprecated code with PhpStan.
 */
class PhpStanTask {

  use SutSettingsTrait;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The "scan contrib" flag.
   *
   * @var bool
   */
  private $scanContrib = FALSE;

  /**
   * The SUT to scan.
   *
   * @var \Acquia\Orca\Fixture\Package|null
   */
  private $sut;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Filesystem $filesystem, Fixture $fixture, PackageManager $package_manager, ProcessRunner $process_runner) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture;
    $this->packageManager = $package_manager;
    $this->processRunner = $process_runner;
  }

  /**
   * Executes the test.
   *
   * @return int
   *   The exit status code.
   */
  public function execute(): int {
    $this->prepareFixture();
    try {
      $command = [
        'phpstan',
        'analyse',
        sprintf('--configuration=%s/phpstan.neon', __DIR__),
      ];
      if ($this->sut) {
        $command[] = $this->sut->getInstallPathAbsolute();
      }
      if ($this->scanContrib) {
        $command[] = $this->getAndEnsurePath('docroot/modules/contrib');
        $command[] = $this->getAndEnsurePath('docroot/profiles/contrib');
        $command[] = $this->getAndEnsurePath('docroot/themes/contrib');
      }
      $this->processRunner->runOrcaVendorBin($command, $this->fixture->getPath());
    }
    catch (ProcessFailedException $e) {
      return StatusCodes::ERROR;
    }
    return StatusCodes::OK;
  }

  /**
   * Gets a fixture path and ensures its presence.
   *
   * @param string $path
   *   The path to ensure.
   *
   * @return string
   *   The absolute path.
   */
  private function getAndEnsurePath(string $path): string {
    $absolute_path = $this->fixture->getPath($path);
    $this->filesystem->mkdir($absolute_path);
    return $absolute_path;
  }

  /**
   * Sets the "scan contrib" flag.
   *
   * @param bool $scan_contrib
   *   TRUE to scan contrib or FALSE not to.
   */
  public function setScanContrib(bool $scan_contrib): void {
    $this->scanContrib = $scan_contrib;
  }

  /**
   * Prepares the fixture for scanning.
   *
   * Deletes various contrib files that cause fatal errors and interrupt scan.
   */
  private function prepareFixture() {
    $files = [
      // @see PF-1879
      'acquia_lift/tests/src/Unit/Polyfill/Drupal.php',
      'acquia_lift/tests/src/Unit/Service/Context/PageContextTest.php',
      'acquia_lift/tests/src/Unit/Service/Helper/HelpMessageHelperTest.php',
      'acquia_lift/tests/src/Unit/Service/Helper/SettingsHelperTest.php',

      // @see https://www.drupal.org/project/blazy/issues/3037752
      'blazy/tests/modules/blazy_test/blazy_test.module',

      // Fixed in Devel 8.x-1.x.
      // @todo Remove when Devel 8.x-1.3 is released.
      'devel/webprofiler/src/DataCollector/AssetsDataCollector.php',
      'devel/webprofiler/src/DataCollector/BlocksDataCollector.php',
      'devel/webprofiler/src/DataCollector/CacheDataCollector.php',
      'devel/webprofiler/src/DataCollector/ConfigDataCollector.php',
      'devel/webprofiler/src/DataCollector/DatabaseDataCollector.php',
      'devel/webprofiler/src/DataCollector/DevelDataCollector.php',
      'devel/webprofiler/src/DataCollector/DrupalDataCollector.php',
      'devel/webprofiler/src/DataCollector/EventsDataCollector.php',
      'devel/webprofiler/src/DataCollector/ExtensionDataCollector.php',
      'devel/webprofiler/src/DataCollector/FormsDataCollector.php',
      'devel/webprofiler/src/DataCollector/HttpDataCollector.php',
      'devel/webprofiler/src/DataCollector/MailDataCollector.php',
      'devel/webprofiler/src/DataCollector/PerformanceTimingDataCollector.php',
      'devel/webprofiler/src/DataCollector/PhpConfigDataCollector.php',
      'devel/webprofiler/src/DataCollector/RoutingDataCollector.php',
      'devel/webprofiler/src/DataCollector/ServicesDataCollector.php',
      'devel/webprofiler/src/DataCollector/StateDataCollector.php',
      'devel/webprofiler/src/DataCollector/ThemeDataCollector.php',
      'devel/webprofiler/src/DataCollector/TranslationsDataCollector.php',
      'devel/webprofiler/src/DataCollector/UserDataCollector.php',
      'devel/webprofiler/src/DataCollector/ViewsDataCollector.php',

      // @see https://www.drupal.org/project/libraries/issues/2882709
      'libraries/src/ExternalLibrary/Utility/LibraryIdAccessorInterface.php',

      // @see https://www.drupal.org/project/libraries/issues/3039243
      'libraries/src/ExternalLibrary/Exception/InvalidLibraryDependencyException.php',

      // @see https://www.drupal.org/project/page_manager/issues/3039249
      'page_manager/page_manager_ui/src/Wizard/RouteParameters.php',
    ];
    foreach ($files as $file) {
      $path = $this->fixture->getPath("docroot/modules/contrib/{$file}");
      $this->filesystem->remove($path);
    }
  }

}
