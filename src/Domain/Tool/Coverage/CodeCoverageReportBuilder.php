<?php

namespace Acquia\Orca\Domain\Tool\Coverage;

use Acquia\Orca\Domain\Tool\Phploc\PhplocTask;
use Acquia\Orca\Exception\OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FinderFactory;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException as NoodlehausParseException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException as FinderDirectoryNotFoundException;

/**
 * Builds a code coverage report.
 */
class CodeCoverageReportBuilder {

  private const FINDER_PATH_EXCLUSIONS = [
    '@docroot/.*@',
    '@var/.*@',
    '@vendor/.*@',
  ];

  /**
   * PHP filename extensions as Finder expects them.
   *
   * @see \Acquia\Orca\Domain\Tool\Phploc\PhplocFacade::PHP_EXTENSIONS
   */
  private const PHP_NAME_PATTERNS = [
    '*.php',
    '*.module',
    '*.theme',
    '*.inc',
    '*.install',
    '*.profile',
    '*.engine',
  ];

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * The Finder factory.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FinderFactory
   */
  private $finderFactory;

  /**
   * The number of exported config files.
   *
   * @var int
   */
  private $numConfigFiles = 0;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The path to analyze.
   *
   * @var string
   */
  private $path = '';

  /**
   * The PHPLOC data.
   *
   * @var \Noodlehaus\Config
   */
  private $phplocData;

  /**
   * The data on tests.
   *
   * @var array
   */
  private $testsData = [
    'classes' => 0,
    'assertions' => 0,
  ];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Helper\Filesystem\FinderFactory $finder_factory
   *   The finder factory.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   */
  public function __construct(ConfigLoader $config_loader, FinderFactory $finder_factory, OrcaPathHandler $orca_path_handler) {
    $this->configLoader = $config_loader;
    $this->finderFactory = $finder_factory;
    $this->orca = $orca_path_handler;
  }

  /**
   * Builds the report.
   *
   * @param string $path
   *   The path to build the report from.
   *
   * @return array
   *   The report data as multidimensional array suitable for
   *   StatusTable::setRows().
   *
   * @see \Acquia\Orca\Console\Helper\StatusTable::setRows()
   *
   * @throws \Exception
   *   In case of errors.
   */
  public function build(string $path): array {
    $this->path = $path;
    $this->ensurePreconditions();
    $this->compileData();
    return $this->buildTable();
  }

  /**
   * Ensures that the preconditions are met.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   *   In case no files are found to scan.
   */
  private function ensurePreconditions(): void {
    $finder = $this->finderFactory->create();
    $php = $finder
      ->in($this->path)
      ->name(self::PHP_NAME_PATTERNS)
      ->notPath(self::FINDER_PATH_EXCLUSIONS);
    if (!iterator_count($php)) {
      throw new OrcaFileNotFoundException('No files found to scan');
    }
  }

  /**
   * Compiles the data from the various sources.
   *
   * @throws \Exception
   *   In case of error.
   */
  private function compileData(): void {
    try {
      $this->getPhplocData();
      $this->getTestsData();
      $this->getConfigFilesData();
    }
    catch (FinderDirectoryNotFoundException $e) {
      throw new OrcaDirectoryNotFoundException($e->getMessage());
    }
  }

  /**
   * Gets the PHPLOC log data.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   *   In case of absent PHPLOC JSON log.
   * @throws \Acquia\Orca\Exception\OrcaParseError
   *   In case of error parsing PHPLOC JSON log.
   */
  private function getPhplocData(): void {
    $log_path = $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH);
    try {
      $config = $this->configLoader->load($log_path);
    }
    catch (NoodlehausFileNotFoundException $e) {
      throw new OrcaFileNotFoundException($e->getMessage());
    }
    catch (NoodlehausParseException $e) {
      throw new OrcaParseError($e->getMessage());
    }
    $this->phplocData = $config->all();
  }

  /**
   * Gets the data on tests.
   *
   * @throws \Acquia\Orca\Exception\OrcaDirectoryNotFoundException
   *   In case of missing directory or non-directory path.
   */
  private function getTestsData(): void {
    $finder = $this->finderFactory->create();
    $classes = $finder
      ->in($this->path)
      ->name('*Test.php')
      ->notPath(self::FINDER_PATH_EXCLUSIONS)
      ->contains('public function test');

    $this->testsData['classes'] = iterator_count($classes);

    foreach ($classes as $file) {
      $contents = $file->getContents();
      $this->testsData['assertions'] += substr_count($contents, '::assert');
      $this->testsData['assertions'] += substr_count($contents, '->assert');
    }
  }

  /**
   * Gets the config files data.
   */
  private function getConfigFilesData(): void {
    $paths = $this->getExtensionPaths();
    foreach ($paths as $path) {
      $this->countConfigFiles($path);
    }
  }

  /**
   * Gets the paths to Drupal extensions.
   */
  private function getExtensionPaths(): array {
    $paths = [];

    $finder = $this->finderFactory->create();
    $info_files = $finder
      ->in($this->path)
      ->name('*.info.yml')
      ->notPath(self::FINDER_PATH_EXCLUSIONS)
      ->notPath('tests')
      ->files();

    foreach ($info_files as $file) {
      $paths[] = $file->getPath();
    }

    return $paths;
  }

  /**
   * Counts exported configuration files under the given path.
   *
   * @param string $path
   *   The path to search for config files.
   *
   * @throws \Acquia\Orca\Exception\OrcaDirectoryNotFoundException
   */
  private function countConfigFiles(string $path): void {
    $finder = $this->finderFactory->create();
    $config_files = $finder
      ->in($path)
      ->path('config')
      ->name('*.yml')
      ->notPath(self::FINDER_PATH_EXCLUSIONS)
      ->notPath('tests');
    $this->numConfigFiles += iterator_count($config_files);
  }

  /**
   * Compiles the report data into a table array.
   *
   * @return array
   *   The report data array.
   */
  private function buildTable(): array {
    return [
      ['Health score', $this->computeHealthScore()],
      ['  Numerator', $this->computeNumerator()],
      ['    Test assertions', $this->getAssertions()],
      ['  Denominator', $this->computeDenominator()],
      ['    Cyclomatic complexity', $this->getComplexity()],
      ['    Exported config files', $this->numConfigFiles],
    ];
  }

  /**
   * Computes the health score.
   *
   * @return float
   *   The score as a floating point number.
   */
  private function computeHealthScore(): float {
    $numerator = $this->computeNumerator();
    $denominator = $this->computeDenominator();

    if (!$numerator || !$denominator) {
      return 0;
    }

    return (float) number_format($numerator / $denominator, 2);
  }

  /**
   * Computes the health score numerator.
   *
   * @return int
   *   The numerator.
   */
  private function computeNumerator(): int {
    return $this->getAssertions();
  }

  /**
   * Computes the health score ratio denominator.
   *
   * @return int
   *   The denominator.
   */
  private function computeDenominator(): int {
    return $this->getComplexity() + $this->numConfigFiles;
  }

  /**
   * Gets the number of test assertions.
   *
   * @return int
   *   The number of assetions.
   */
  private function getAssertions(): int {
    return $this->testsData['assertions'];
  }

  /**
   * Gets the cyclomatic complexity.
   *
   * @return int
   *   The cyclomatic complexity.
   */
  private function getComplexity(): int {
    return $this->phplocData['ccn'];
  }

}
