<?php

namespace Acquia\Orca\Console\Command\Debug\Helper;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds a Drupal core versions Symfony Console table.
 */
class CoreVersionsTableBuilder {

  /**
   * The Drupal core version resolver.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionResolver;

  /**
   * Whether or not to display "Example" column.
   *
   * @var bool
   */
  private $includeExamples = FALSE;

  /**
   * Whether or not to display the "Resolved" column.
   *
   * @var bool
   */
  private $includeResolved = FALSE;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version resolver.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_resolver) {
    $this->drupalCoreVersionResolver = $drupal_core_version_resolver;
  }

  /**
   * Builds the table.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   * @param bool $include_examples
   *   TRUE to include the "Example" column or FALSE not to.
   * @param bool $include_resolved
   *   TRUE to include the "Resolved" column or FALSE not to.
   *
   * @return \Symfony\Component\Console\Helper\Table
   *   The table built.
   */
  public function build(OutputInterface $output, bool $include_examples, bool $include_resolved): Table {
    $this->includeExamples = $include_examples;
    $this->includeResolved = $include_resolved;
    return (new Table($output))
      ->setHeaders($this->getHeaders())
      ->setRows($this->getRows());
  }

  /**
   * Gets the table headers.
   *
   * @return string[]
   *   The table headers.
   */
  private function getHeaders(): array {
    $headers = [];
    $headers[] = 'Version';
    if ($this->includeExamples) {
      $headers[] = 'Example';
    }
    if ($this->includeResolved) {
      $headers[] = 'Resolved';
    }
    $headers[] = 'Description';
    return $headers;
  }

  /**
   * Gets the table rows.
   *
   * @return array
   *   The table rows.
   */
  private function getRows(): array {
    $rows = [];
    foreach ($this->getVersions() as $version) {
      $cells = [];
      $cells[] = $version->getKey();
      if ($this->includeExamples) {
        $cells[] = $version->getExample();
      }
      if ($this->includeResolved) {
        $cells[] = $this->getResolvedVersion($version);
      }
      $cells[] = $version->getDescription();
      $rows[] = $cells;
    }
    return $rows;
  }

  /**
   * Gets the version enums.
   *
   * This method is extracted exclusively for testability.
   *
   * @return \Acquia\Orca\Enum\DrupalCoreVersionEnum[]
   *   The version enums.
   *
   * @codeCoverageIgnore
   */
  protected function getVersions(): array {
    return DrupalCoreVersionEnum::values();
  }

  /**
   * Gets the resolved version number for a given version enum.
   *
   * @param \Acquia\Orca\Enum\DrupalCoreVersionEnum $version
   *   The version enum.
   *
   * @return string
   *   The resolved version string.
   */
  private function getResolvedVersion(DrupalCoreVersionEnum $version): string {
    try {
      $value = $this->drupalCoreVersionResolver->resolvePredefined($version);
    }
    catch (OrcaVersionNotFoundException $e) {
      $value = '~';
    }
    return $value;
  }

}
