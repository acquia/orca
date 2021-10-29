<?php

namespace Acquia\Orca\Domain\Fixture\Helper;

use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides facilities for working with Drupal settings.php files.
 */
class DrupalSettingsHelper {

  private const CI_SETTINGS_PATH = 'docroot/sites/default/settings/ci.settings.php';

  private const LOCAL_SETTINGS_PATH = 'docroot/sites/default/settings/local.settings.php';

  private const SETTINGS_PHP_PATH = 'docroot/sites/default/settings.php';

  private const DEFAULT_SETTINGS_PHP_PATH = 'docroot/sites/default/default.settings.php';

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

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
  protected $options;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   */
  public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
  }

  /**
   * Ensures that Drupal is correctly configured.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   * @param bool $has_blt
   *   Whether the fixture contains BLT.
   */
  public function ensureSettings(FixtureOptions $options, bool $has_blt): void {
    $this->options = $options;
    $this->ensureCiSettingsFile();
    $this->ensureLocalSettingsFile();
    $this->ensureSettingsFileInclude($has_blt);
  }

  /**
   * Ensures that the CI settings file is correctly configured.
   */
  private function ensureCiSettingsFile(): void {
    $path = $this->fixture->getPath(self::CI_SETTINGS_PATH);

    $data = '<?php' . PHP_EOL . PHP_EOL;
    $data .= $this->getSettings();

    $this->filesystem->appendToFile($path, $data);
  }

  /**
   * Ensures that the local settings file is correctly configured.
   */
  private function ensureLocalSettingsFile(): void {
    $path = $this->fixture->getPath(self::LOCAL_SETTINGS_PATH);

    $data = NULL;
    if ($this->filesystem->exists($path)) {
      $data .= PHP_EOL;
    }
    else {
      $data .= '<?php' . PHP_EOL . PHP_EOL;
    }
    $data .= $this->getSettings();

    $this->filesystem->appendToFile($path, $data);
  }

  /**
   * Ensures that settings.php includes the local settings file we generated.
   */
  private function ensureSettingsFileInclude(bool $has_blt): void {
    // BLT provides this include.
    if ($has_blt) {
      return;
    }

    $settings_path = $this->fixture->getPath(self::SETTINGS_PHP_PATH);
    $default_settings_path = $this->fixture->getPath(self::DEFAULT_SETTINGS_PHP_PATH);

    $this->filesystem->copy($default_settings_path, $settings_path);

    $data = PHP_EOL;
    $data .= $this->getSettingsInclude();

    $this->filesystem->appendToFile($settings_path, $data);
  }

  /**
   * Gets the PHP code to add to the Drupal settings files.
   *
   * @return string
   *   A string of PHP code.
   */
  protected function getSettings(): string {
    $data = '# ORCA settings.' . PHP_EOL;

    if ($this->options->useSqlite()) {
      $data .= <<<'PHP'
$databases['default']['default']['database'] = dirname(DRUPAL_ROOT) . '/db.sqlite';
$databases['default']['default']['driver'] = 'sqlite';
unset($databases['default']['default']['namespace']);
PHP;
    }

    $data .= PHP_EOL . <<<'PHP'
// Override the definition of the service container used during Drupal
// bootstraps so that the core db-tools.php script can import database dumps
// properly. Without this, a cache_container table will be created in the
// destination database before the import begins, making Drupal think it's
// already installed and causing the import to fail.
// @see \Drupal\Core\DrupalKernel::$defaultBootstrapContainerDefinition
// @see https://www.drupal.org/project/drupal/issues/3006038
$settings['bootstrap_container_definition'] = [
  'parameters' => [],
  'services' => [
    'database' => [
      'class' => 'Drupal\Core\Database\Connection',
      'factory' => 'Drupal\Core\Database\Database::getConnection',
      'arguments' => ['default'],
    ],
    'cache.container' => [
      'class' => 'Drupal\Core\Cache\MemoryBackend',
    ],
    'cache_tags_provider.container' => [
      'class' => 'Drupal\Core\Cache\DatabaseCacheTagsChecksum',
      'arguments' => ['@database'],
    ],
  ],
];

// Change the config cache to use a memory backend to prevent SQLite "too many
// SQL variables" errors.
// @see https://www.drupal.org/project/drupal/issues/2031261
$settings['cache']['bins']['config'] = 'cache.backend.memory';
PHP;
    return $data;
  }

  /**
   * Gets the PHP code to add to Drupal's settings.php.
   *
   * @return string
   *   A string of PHP code.
   */
  protected function getSettingsInclude(): string {
    $data = '# ORCA settings.' . PHP_EOL;

    $data .= <<<'PHP'
if (file_exists($app_root . '/' . $site_path . '/settings/local.settings.php')) {
  include $app_root . '/' . $site_path . '/settings/local.settings.php';
}
PHP;
    return $data;
  }

}
