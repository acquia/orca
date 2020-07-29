<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Facade\DrushFacade;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Server\WebServer;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a fixture.
 */
class FixtureInspector {

  /**
   * The fixture's composer.json config.
   *
   * @var \Noodlehaus\Config|null
   */
  private $composerJson;

  /**
   * The fixture's composer.lock config.
   *
   * @var \Noodlehaus\Config|null
   */
  private $composerLock;

  /**
   * The Drush facade.
   *
   * @var \Acquia\Orca\Facade\DrushFacade
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
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * The subextension manager.
   *
   * @var \Acquia\Orca\Fixture\SubextensionManager
   */
  private $subextensionManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Facade\DrushFacade $drush
   *   The Drush facade.
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   */
  public function __construct(DrushFacade $drush, FixturePathHandler $fixture_path_handler, SymfonyStyle $output, PackageManager $package_manager, SubextensionManager $subextension_manager) {
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
    // Drupal Console is not installed after Drupal 8.
    if ($this->getInstalledPackageVersion('drupal/console')) {
      $overview[] = [
        'Drupal Console version',
        $this->getInstalledPackageVersionPretty('drupal/console'),
      ];
    }

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
    $key = 'extra.orca.sut';

    if (!$this->getComposerJson()->has($key)) {
      return 'Unknown';
    }

    return (string) $this->getComposerJson()->get($key);
  }

  /**
   * Gets the composer.json config.
   *
   * @return \Noodlehaus\Config|null
   *   The composer.json config if available or NULL if not.
   */
  private function getComposerJson() {
    if (!$this->composerJson) {
      $this->composerJson = new Config($this->fixture->getPath('composer.json'));
    }

    return $this->composerJson;
  }

  /**
   * Gets the fixture type.
   *
   * @return string
   *   The fixture type, i.e., "Bare", "No SUT", "SUT-only", "Standard", or
   *   "Unknown".
   */
  private function getFixtureType(): string {
    if ($this->isBare()) {
      return 'Bare';
    }

    if (!$this->getSutName()) {
      return 'No SUT';
    }

    $key = 'extra.orca.is-sut-only';

    if (!$this->getComposerJson()->has($key)) {
      return 'Unknown';
    }

    return $this->getComposerJson()->get($key) ? 'SUT-only' : 'Standard';
  }

  /**
   * Gets the package stability setting.
   *
   * @return string
   *   The package stability setting, i.e., "Dev/HEAD", "Stable", or "Unknown".
   */
  private function getPackageStabilitySetting(): string {
    $key = 'extra.orca.is-dev';

    if (!$this->getComposerJson()->has($key)) {
      return 'Unknown';
    }

    return $this->getComposerJson()->get($key) ? 'Dev/HEAD' : 'Stable';
  }

  /**
   * Gets the Composer project template used to create the fixture.
   *
   * @return string
   *   The project template package/constraint string, e.g.,
   *   acquia/drupal-recommended-project or acquia/blt-project:12.x.
   */
  private function getProjectTemplate(): string {
    $key = 'extra.orca.project-template';

    if (!$this->getComposerJson()->has($key)) {
      return 'Unknown';
    }

    return $this->getComposerJson()->get($key);
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
    if (!is_null($this->drushStatus)) {
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
   * @return \Noodlehaus\Config|null
   *   The composer.lock config if available or NULL if not.
   */
  private function getComposerLock() {
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

  /**
   * Determines whether or not the fixture is bare.
   *
   * @return bool
   *   TRUE if the fixture is bare or FALSE if not.
   */
  private function isBare(): bool {
    $key = 'extra.orca.is-bare';

    if (!$this->getComposerJson()->has($key)) {
      return FALSE;
    }

    return (bool) $this->getComposerJson()->get($key);
  }

}
