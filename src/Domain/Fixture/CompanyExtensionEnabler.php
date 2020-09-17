<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Drush\Drush;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Installs company Drupal extensions.
 */
class CompanyExtensionEnabler {

  private const TYPE_MODULE = 'drupal-module';

  private const TYPE_THEME = 'drupal-theme';

  /**
   * The fixture composer.json helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
   */
  private $composerJsonHelper;

  /**
   * The Drush facade.
   *
   * @var \Acquia\Orca\Domain\Drush\Drush
   */
  private $drush;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The subextension manager.
   *
   * @var \Acquia\Orca\Domain\Fixture\SubextensionManager
   */
  private $subextensionManager;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Options\FixtureOptions
   */
  private $options;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper $composer_json_helper
   *   The fixture composer.json helper.
   * @param \Acquia\Orca\Domain\Drush\Drush $drush
   *   The Drush facade.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Domain\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\FixtureNotExistsException
   * @throws \Acquia\Orca\Exception\InvalidArgumentException
   * @throws \Acquia\Orca\Exception\ParseError
   */
  public function __construct(ComposerJsonHelper $composer_json_helper, Drush $drush, Filesystem $filesystem, PackageManager $package_manager, SymfonyStyle $output, SubextensionManager $subextension_manager) {
    $this->composerJsonHelper = $composer_json_helper;
    $this->drush = $drush;
    $this->filesystem = $filesystem;
    $this->output = $output;
    $this->packageManager = $package_manager;
    $this->subextensionManager = $subextension_manager;
  }

  /**
   * Enables extensions.
   *
   * @throws \Exception
   */
  public function enable(): void {
    $this->options = $this->composerJsonHelper->getFixtureOptions();
    $this->enableAcquiaExtensions();
  }

  /**
   * Enables the company extensions.
   */
  private function enableAcquiaExtensions(): void {
    if ($this->options->isBare()) {
      $this->output->warning('No extensions to enable because the fixture is bare');
      return;
    }

    /* @var \Acquia\Orca\Domain\Package\Package $sut */
    $sut = $this->options->getSut();
    if ($this->options->isSutOnly() && !$sut->isDrupalExtension()) {
      $this->output->warning('No extensions to enable because the fixture is SUT-only and the SUT is not a Drupal extension');
      return;
    }

    $this->output->section('Enabling company modules & themes');
    $this->enableModules();
    $this->enableThemes();
  }

  /**
   * Enables the company modules.
   */
  private function enableModules(): void {
    $modules = $this->getCompanyExtensionList(self::TYPE_MODULE);
    if (!$modules) {
      return;
    }
    $this->drush->enableModules($modules);
  }

  /**
   * Enables the company themes.
   */
  private function enableThemes(): void {
    $theme_list = $this->getCompanyExtensionList(self::TYPE_THEME);
    if (!$theme_list) {
      return;
    }
    $this->drush->enableThemes($theme_list);
  }

  /**
   * Gets the list of company extensions to enable.
   *
   * @param string $extension_type
   *   The extension type: ::TYPE_MODULE or ::TYPE_THEME.
   *
   * @return string[]
   *   An indexed array of company extension machine names.
   */
  private function getCompanyExtensionList(string $extension_type): array {
    $extension_list = [];

    $top_level_packages = $this->packageManager->getAll();
    if ($this->options->isSutOnly()) {
      $top_level_packages = [$this->options->getSut()];
    }

    foreach ($top_level_packages as $package) {
      if ($this->shouldGetEnabled($package, $extension_type)) {
        $extension_list[] = $package->getProjectName();
      }

      if (!$package->isDrupalExtension()) {
        continue;
      }

      foreach ($this->subextensionManager->getByParent($package) as $subextension) {
        if (!$this->shouldGetEnabled($subextension, $extension_type)) {
          continue;
        }
        $extension_list[] = $subextension->getDrupalExtensionName();
      }
    }

    return $extension_list;
  }

  /**
   * Determines whether or not a given packages should get enabled.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package to consider.
   * @param string $extension_type
   *   The type of extension that should get enabled: ::TYPE_MODULE or
   *   ::TYPE_THEME.
   *
   * @return bool
   *   TRUE if the given package should be enabled or FALSE if not.
   */
  private function shouldGetEnabled(Package $package, string $extension_type): bool {
    return $package->getType() === $extension_type
      && $package->shouldGetEnabled()
      && $this->filesystem->exists($package->getInstallPathAbsolute());
  }

}
