<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugPackagesCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'debug:packages';

  /**
   * The "core" argument.
   *
   * @var string|string[]|null
   */
  private $coreVersion;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The Drupal core version finder.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionFinder;

  /**
   * The version parser.
   *
   * @var \Composer\Semver\VersionParser
   */
  private $versionParser;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_finder
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Composer\Semver\VersionParser $version_parser
   *   The version parser.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_finder, PackageManager $package_manager, VersionParser $version_parser) {
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    $this->packageManager = $package_manager;
    $this->versionParser = $version_parser;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['packages'])
      ->addArgument('core', InputArgument::OPTIONAL, implode(PHP_EOL, array_merge(
        ['A Drupal core version to target:'],
        DrupalCoreVersionEnum::commandArgumentHelp()
      )))
      ->setDescription('Displays the active packages configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $argument = $input->getArgument('core');
    try {
      $this->handleCoreArgument($argument);
    }
    catch (\InvalidArgumentException $e) {
      $output->writeln([
        sprintf('Error: Invalid value for "core" option: "%s".', $argument),
        sprintf('Hint: Acceptable values are "%s", or any version string Composer understands.', implode('", "', DrupalCoreVersionEnum::values())),
      ]);
      return StatusCodeEnum::ERROR;
    }

    (new Table($output))
      ->setHeaderTitle("Drupal {$this->coreVersion}")
      ->setHeaders($this->getHeaders())
      ->setRows($this->getRows())
      ->render();
    return StatusCodeEnum::OK;
  }

  /**
   * Handles the "core" command argument.
   *
   * @param string|string[]|null $argument
   *   The "core" command argument.
   */
  private function handleCoreArgument($argument): void {
    if ($argument === NULL) {
      $argument = '*';
    }

    if (!is_string($argument)) {
      throw new \InvalidArgumentException();
    }

    if (DrupalCoreVersionEnum::isValid($argument)) {
      $version = new DrupalCoreVersionEnum($argument);
      $argument = $this->drupalCoreVersionFinder->resolvePredefined($version);
    }

    try {
      $this->versionParser->parseConstraints($argument);
      $this->coreVersion = $argument;
    }
    catch (\UnexpectedValueException $e) {
      throw new \InvalidArgumentException();
    }
  }

  /**
   * Gets the table headers.
   *
   * @return string[]
   *   An array of headers.
   */
  private function getHeaders(): array {
    return [
      'Package',
      'type',
      'install_path',
      'url',
      'version',
      'version_dev',
      'enable',
    ];
  }

  /**
   * Gets the table rows.
   *
   * @return string[]
   *   An array of table rows.
   */
  private function getRows(): array {
    $rows = [];
    foreach ($this->packageManager->getCompanyPackages() as $package) {
      $rows[] = [
        $package->getPackageName(),
        $package->getType(),
        $package->getInstallPathRelative(),
        $package->getRepositoryUrlRaw(),
        $package->getVersionRecommended($this->coreVersion),
        $package->getVersionDev($this->coreVersion),
        $package->shouldGetEnabled() ? 'yes' : '',
      ];
    }
    return $rows;
  }

}
