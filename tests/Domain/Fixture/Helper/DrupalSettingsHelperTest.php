<?php

namespace Acquia\Orca\Tests\Domain\Fixture\Helper;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Fixture\Helper\DrupalSettingsHelper;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 */
class DrupalSettingsHelperTest extends TestCase {

  private const CI_SETTINGS_PATH = 'docroot/sites/default/settings/ci.settings.php';

  private const LOCAL_SETTINGS_PATH = 'docroot/sites/default/settings/local.settings.php';

  private const SETTINGS_PHP_PATH = 'docroot/sites/default/settings.php';

  private const DEFAULT_SETTINGS_PHP_PATH = 'docroot/sites/default/default.settings.php';

  protected function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  private function createDrupalSettingsHelperWithDummyData(): DrupalSettingsHelper {
    $filesystem = $this->filesystem->reveal();
    $fixture = $this->fixture->reveal();
    return new class ($filesystem, $fixture) extends DrupalSettingsHelper {

      protected function getSettings(): string {
        return 'SETTINGS';
      }

      protected function getSettingsInclude(): string {
        return 'SETTINGS';
      }

    };
  }

  private function createDrupalSettingsHelperWithOptions($filesystem, $fixture, FixtureOptions $options) {
    return new class ($filesystem, $fixture, $options) extends DrupalSettingsHelper {

      public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler, FixtureOptions $options) {
        $this->options = $options;
        parent::__construct($filesystem, $fixture_path_handler);
      }

      // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
      public function getSettings(): string {
        return parent::getSettings();
      }

    };
  }

  private function createFixtureOptions($options): FixtureOptions {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new FixtureOptions($drupal_core_version_finder, $package_manager, $options);
  }

  public function testEnsureNoBlt(): void {
    $this->filesystem
      ->appendToFile(self::CI_SETTINGS_PATH, '<?php' . PHP_EOL . PHP_EOL . 'SETTINGS')
      ->shouldBeCalledOnce();
    $this->filesystem
      ->appendToFile(self::LOCAL_SETTINGS_PATH, '<?php' . PHP_EOL . PHP_EOL . 'SETTINGS')
      ->shouldBeCalledOnce();
    $this->filesystem
      ->exists(self::LOCAL_SETTINGS_PATH)
      ->shouldBeCalledOnce();
    $this->filesystem
      ->copy(self::DEFAULT_SETTINGS_PHP_PATH, self::SETTINGS_PHP_PATH)
      ->shouldBeCalledOnce();
    $this->filesystem
      ->appendToFile(self::SETTINGS_PHP_PATH, PHP_EOL . 'SETTINGS')
      ->shouldBeCalledOnce();
    $options = $this->createFixtureOptions([]);
    $helper = $this->createDrupalSettingsHelperWithDummyData();

    $helper->ensureSettings($options, FALSE);
  }

  public function testEnsureBlt(): void {
    $this->filesystem
      ->appendToFile(self::CI_SETTINGS_PATH, '<?php' . PHP_EOL . PHP_EOL . 'SETTINGS')
      ->shouldBeCalledOnce();
    $this->filesystem
      ->appendToFile(self::LOCAL_SETTINGS_PATH, PHP_EOL . 'SETTINGS')
      ->shouldBeCalledOnce();
    $this->filesystem
      ->exists(self::LOCAL_SETTINGS_PATH)
      ->willReturn(TRUE)
      ->shouldBeCalledOnce();
    $options = $this->createFixtureOptions([]);
    $helper = $this->createDrupalSettingsHelperWithDummyData();

    $helper->ensureSettings($options, TRUE);
  }

  public function testGetSettingsDefault(): void {
    $filesystem = $this->filesystem->reveal();
    $fixture = $this->fixture->reveal();
    $options = $this->createFixtureOptions([]);
    $helper = $this->createDrupalSettingsHelperWithOptions($filesystem, $fixture, $options);

    $settings = $helper->getSettings();

    self::assertContains('# ORCA settings.' . PHP_EOL, $settings);
    self::assertContains('bootstrap_container_definition', $settings);
    self::assertContains('sqlite', $settings);
  }

  public function testGetSettingsNoSqlite(): void {
    $filesystem = $this->filesystem->reveal();
    $fixture = $this->fixture->reveal();
    $options = $this->createFixtureOptions(['no-sqlite' => TRUE]);
    $helper = $this->createDrupalSettingsHelperWithOptions($filesystem, $fixture, $options);

    $settings = $helper->getSettings();

    self::assertNotContains('sqlite', $settings);
  }

}
