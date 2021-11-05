<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Drush\DrushFacade;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Server\WebServer;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a fixture.
 */
class FixtureInspector {

  /**
   * The fixture composer.json helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
   */
  private $composerJsonHelper;

  /**
   * The fixture's composer.lock config.
   *
   * @var \Noodlehaus\Config|null
   */
  private $composerLock;

  /**
   * The Drush facade.
   *
   * @var \Acquia\Orca\Domain\Drush\DrushFacade
   */
  private $drush;

  /**
   * The Drush core status data.
   *
   * @var array|null
   */
  private $drushStatus;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Options\FixtureOptions
   */
  private $options;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The subextension manager.
   *
   * @var \Acquia\Orca\Domain\Fixture\SubextensionManager
   */
  private $subextensionManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper $composer_json_helper
   *   The fixture composer.json helper.
   * @param \Acquia\Orca\Domain\Drush\DrushFacade $drush
   *   The Drush facade.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Domain\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function __construct(ComposerJsonHelper $composer_json_helper, DrushFacade $drush, FixturePathHandler $fixture_path_handler, SymfonyStyle $output, PackageManager $package_manager, SubextensionManager $subextension_manager) {
    $this->composerJsonHelper = $composer_json_helper;
    $this->drush = $drush;
    $this->fixture = $fixture_path_handler;
    $this->output = $output;
    $this->packageManager = $package_manager;
    $this->subextensionManager = $subextension_manager;
  }

  /**
   * Gets an overview of the fixture.
   *
   * @return array
   *   An indexed array of data columns ([Label, Value]).
   */
  public function getOverview(): array {
    $this->options = $this->composerJsonHelper->getFixtureOptions();

    $overview = [];

    $overview[] = ['Fixture directory', $this->fixture->getPath()];
    $overview[] = ['Site URI', sprintf('http://%s', WebServer::WEB_ADDRESS)];
    $overview[] = ['System under test (SUT)', $this->getSutNamePretty()];
    $overview[] = ['Fixture type', $this->getFixtureType()];
    $overview[] = ['Package stability', $this->getPackageStabilitySetting()];
    $overview[] = ['Project template', $this->getProjectTemplate()];
    $overview[] = [
      'Install profile',
      $this->getDrushStatusField('install-profile'),
    ];
    $overview[] = ['Default theme', $this->getDrushStatusField('theme')];
    $overview[] = ['Admin theme', $this->getDrushStatusField('admin-theme')];
    $overview[] = [
      'Drupal core version',
      $this->getInstalledPackageVersionPretty('drupal/core'),
    ];
    $overview[] = [
      'Drush version',
      $this->getInstalledPackageVersionPretty('drush/drush'),
    ];

    $overview = array_merge($overview, $this->getInstalledPackages());

    return $overview;
  }

  /**
   * Gets a pretty form of the SUT name.
   *
   * @return string
   *   The SUT name if available (e.g., "drupal/example" or "None") or "Unknown"
   *   if not.
   */
  private function getSutNamePretty(): string {
    $name = $this->getSutName();

    if (!$name) {
      return 'None';
    }

    return $name;
  }

  /**
   * Gets the SUT name.
   *
   * @return string
   *   The SUT name if available (e.g., "drupal/example") or "Unknown" if not.
   */
  private function getSutName(): string {
    if (!$this->options->hasSut()) {
      return 'None';
    }

    /** @var \Acquia\Orca\Domain\Package\Package $sut */
    $sut = $this->options->getSut();
    return $sut->getPackageName();
  }

  /**
   * Gets the fixture type.
   *
   * @return string
   *   The fixture type, i.e., "Bare", "No SUT", "SUT-only", "Standard", or
   *   "Unknown".
   */
  private function getFixtureType(): string {
    if ($this->options->isBare()) {
      return 'Bare';
    }
    if (!$this->options->hasSut()) {
      return 'No SUT';
    }
    if ($this->options->isSutOnly()) {
      return 'SUT-only';
    }
    return 'Standard';
  }

  /**
   * Gets the package stability setting.
   *
   * @return string
   *   The package stability setting, i.e., "Dev/HEAD", "Stable", or "Unknown".
   */
  private function getPackageStabilitySetting(): string {
    if ($this->options->isDev()) {
      return 'Dev/HEAD';
    }
    return 'Stable';
  }

  /**
   * Gets the Composer project template used to create the fixture.
   *
   * @return string
   *   The project template package/constraint string, e.g.,
   *   acquia/drupal-recommended-project.
   */
  private function getProjectTemplate(): string {
    return $this->options->getProjectTemplate();
  }

  /**
   * Gets the value of a given field from Drush's core status output.
   *
   * @param string $field
   *   The field name.
   *
   * @return string
   *   The field value if available or an exclamation mark (!) if not.
   */
  private function getDrushStatusField(string $field): string {
    $json = $this->getDrushStatusJson();
    if (!array_key_exists($field, $json) || !is_string($json[$field])) {
      return '!';
    }
    return $json[$field];
  }

  /**
   * Gets Drush's core status JSON representation.
   *
   * @return array
   *   The retrieved status data if available, or an empty array if not.
   */
  private function getDrushStatusJson(): array {
    if ($this->drushStatus !== NULL) {
      return $this->drushStatus;
    }

    $this->drushStatus = $this->drush->getDrushStatus();

    if (!is_array($this->drushStatus)) {
      $this->output->warning('Could not retrieve Drush status info. Some fixture details, denoted with an exclamation mark (!), are unavailable.');
      $this->drushStatus = [];
    }

    return $this->drushStatus;
  }

  /**
   * Gets the installed version of a given package.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return string
   *   The installed version of the given package if available (e.g., "1.0.0")
   *   or a tilde (~) if not.
   */
  public function getInstalledPackageVersionPretty(string $package_name): string {
    $version = $this->getInstalledPackageVersion($package_name);

    if (!$version) {
      return '~';
    }

    return $version;
  }

  /**
   * Gets the installed version of a given package.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return string|null
   *   The installed version of the given package if available (e.g., "1.0.0")
   *   or NULL if not.
   */
  public function getInstalledPackageVersion(string $package_name): ?string {
    $packages = [];
    foreach ($this->getComposerLock()->get('packages') as $package) {
      $packages[$package['name']] = $package['version'];
    }

    if (!array_key_exists($package_name, $packages)) {
      return NULL;
    }

    return $packages[$package_name];
  }

  /**
   * Gets the composer.lock config.
   *
   * @return \Noodlehaus\Config
   *   The composer.lock config.
   */
  private function getComposerLock(): Config {
    if (!$this->composerLock) {
      $this->composerLock = new Config($this->fixture->getPath('composer.lock'), new Json());
    }

    return $this->composerLock;
  }

  /**
   * Gets the list of installed packages.
   *
   * @return array
   *   An indexed array of installed packages containing the following values:
   *   - The package label, e.g., "drupal/example", prefixed with "  - " if it's
   *     a subextension and suffixed with a trailing asterisk (*) if it's the
   *     SUT.
   *   - The installed package version, e.g., "1.0.0".
   */
  private function getInstalledPackages(): array {
    $packages = [new TableSeparator()];
    foreach ($this->packageManager->getAll() as $package_name => $package) {
      $label = $package_name;
      if ($package_name === $this->getSutName()) {
        $label = "{$package_name} *";
      }
      $version = $this->getInstalledPackageVersionPretty($package_name);
      $packages[] = [$label, $version];
      if (!$this->getInstalledPackageVersion($package_name)) {
        continue;
      }
      $parent_version = $version;
      foreach (array_keys($this->subextensionManager->getByParent($package)) as $subextension_name) {
        if ($version === $parent_version) {
          $version = '"';
        }
        $packages[] = ["  - {$subextension_name}", $version];
      }
    }
    return $packages;
  }

}
