<?php

namespace Acquia\Orca\Fixture;

use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Symfony\Component\Console\Helper\TableSeparator;

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
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(Fixture $fixture, PackageManager $package_manager) {
    $this->fixture = $fixture;
    $this->packageManager = $package_manager;
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
    $overview[] = ['Site URI', sprintf('http://%s', Fixture::WEB_ADDRESS)];
    $overview[] = ['System under test (SUT)', $this->getSutNamePretty()];
    $overview[] = ['Fixture type', $this->getFixtureType()];
    $overview[] = ['Package stability', $this->getPackageStabilitySetting()];
    $overview[] = ['Drupal core version', $this->getInstalledPackageVersion('drupal/core')];
    $overview[] = ['Drush version', $this->getInstalledPackageVersion('drush/drush')];

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
   *   The fixture type, i.e., "No SUT", "SUT-only", "Standard", or "Unknown".
   */
  private function getFixtureType(): string {
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
   * Gets the installed version of a given package.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return string
   *   The installed version of the given package if available (e.g., "1.0.0")
   *   or a tilde (~) if not.
   */
  private function getInstalledPackageVersion(string $package_name): string {
    $packages = [];
    foreach ($this->getComposerLock()->get('packages') as $package) {
      $packages[$package['name']] = $package['version'];
    }

    if (!array_key_exists($package_name, $packages)) {
      return '~';
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
   *   - The package label, e.g., "drupal/example" (with a trailing asterisk (*)
   *     if it's the SUT.
   *   - The installed package version, e.g., "1.0.0".
   */
  private function getInstalledPackages(): array {
    $packages = [new TableSeparator()];
    foreach (array_keys($this->packageManager->getMultiple()) as $package_name) {
      $label = $package_name;
      if ($package_name === $this->getSutName()) {
        $label = "{$package_name} *";
      }
      $packages[] = [$label, $this->getInstalledPackageVersion($package_name)];
    }
    return $packages;
  }

}
